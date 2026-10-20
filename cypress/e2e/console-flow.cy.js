// End-to-end proof for the ESP8266 console contract.
//
// The console is impersonated with cy.request: it speaks the same JSON the
// firmware sends (register -> pending-transaction -> tap -> heartbeat) against
// the real Laravel app and SQLite database. The cashier side is the real POS
// Livewire page, so a successful tap has to show up in the browser through the
// status polling the hardware relies on.

const DEVICE_ID = 'ESP8266_DEMO_001'
// Seeded card owned by "Siti Rahayu" (Rp 75.000). admin-flow blocks the first
// seeded card (A1B2C3D4), so the console spec uses the second one.
const CARD_UID = 'E5F6G7H8'

let livewireCalls = 0
before(() => {
  cy.intercept('POST', '/livewire/update', () => {
    livewireCalls += 1
  }).as('lw')
})

const rupiah = (n) => 'Rp ' + Number(n).toLocaleString('id-ID')

function clickUntil(selector, predicate, { index = 0, timeout = 15000 } = {}) {
  const deadline = Date.now() + timeout

  const attempt = () => {
    const before = livewireCalls

    cy.get(selector).eq(index).click({ force: true })
    cy.wait(400)

    cy.get('body').then(($body) => {
      const doc = $body[0].ownerDocument
      const fired = livewireCalls > before

      if (predicate(doc) || fired || Date.now() > deadline) {
        return
      }

      attempt()
    })
  }

  attempt()
}

const cartHasItem = (doc) => doc.querySelectorAll('.cart-item').length >= 1
const waitingForTap = (doc) => !!doc.querySelector('button[wire\\:click="cancelTransaction"]')

// Click checkout until Livewire really handled it, but never twice: a second
// request would create a second pending bill.
function checkout() {
  const deadline = Date.now() + 15000

  const attempt = () => {
    const before = livewireCalls

    cy.get('button[wire\\:click="checkout"]').click({ force: true })
    cy.wait(500)

    cy.get('body').then(($body) => {
      const doc = $body[0].ownerDocument
      const fired = livewireCalls > before

      if (waitingForTap(doc) || fired || Date.now() > deadline) {
        return
      }

      attempt()
    })
  }

  attempt()
  cy.get('button[wire\\:click="cancelTransaction"]', { timeout: 15000 }).should('be.visible')
}

function registerConsole() {
  cy.request('POST', '/api/device/register', {
    device_id: DEVICE_ID,
    firmware_version: '1.0.0',
  }).then((response) => {
    expect(response.status).to.eq(200)
    cy.wrap(response.body.device.api_token).as('consoleToken')
  })
}

function consoleRequest(method, path, body) {
  return cy.get('@consoleToken').then((token) => {
    const options = {
      method,
      url: `/api/device/${DEVICE_ID}${path}`,
      headers: { Authorization: `Bearer ${token}` },
      failOnStatusCode: false,
    }

    if (body !== undefined) {
      options.body = body
    }

    return cy.request(options)
  })
}

function startOrder() {
  clickUntil('.product-card', cartHasItem)
  checkout()
}

describe('Console Flow (firmware contract + POS)', () => {
  beforeEach(() => {
    cy.loginKasir('kasir@skanilan.local', 'password123')
    cy.visit('/kasir/pos')
    cy.get('.product-grid').should('exist')
    registerConsole()
  })

  it('registers the console and completes a real card tap end to end', () => {
    startOrder()

    // The console sees the bill through the polling endpoint. Assert the exact
    // fields firmware/src/network.cpp reads.
    consoleRequest('GET', '/pending-transaction').then((response) => {
      expect(response.status).to.eq(200)
      expect(response.body.success).to.eq(true)
      expect(response.body.has_pending).to.eq(true)

      const tx = response.body.transaction
      expect(tx.id).to.be.a('number')
      expect(tx.items).to.be.an('array').and.have.length(1)
      expect(tx.items[0]).to.include.keys('product_id', 'name', 'qty', 'subtotal')
      expect(Number(tx.total)).to.be.greaterThan(0)
      expect(tx.expires_at).to.be.a('string')
      expect(tx.remaining_seconds).to.be.a('number')
      expect(tx.remaining_seconds).to.be.greaterThan(0)
      expect(tx.remaining_seconds).to.be.at.most(30)
    })

    consoleRequest('POST', '/tap', { uid: CARD_UID }).then((response) => {
      expect(response.status).to.eq(200)
      expect(response.body.success).to.eq(true)

      const tx = response.body.transaction
      expect(Number(tx.balance_after)).to.eq(Number(tx.balance_before) - Number(tx.total))
      expect(tx.card.uid).to.eq(CARD_UID)

      // The browser has to learn about it through the status poll, exactly like
      // the cashier demo does.
      cy.contains('Transaksi berhasil!', { timeout: 15000 }).should('be.visible')
      cy.contains(tx.card.user_name).should('be.visible')
      cy.contains(CARD_UID).should('be.visible')
      cy.contains(rupiah(tx.balance_after)).should('be.visible')
    })
  })

  it('shows the same rejection on the POS when the card is unknown', () => {
    startOrder()

    consoleRequest('POST', '/tap', { uid: 'UNKNOWN_CARD_XYZ' }).then((response) => {
      expect(response.status).to.eq(400)
      expect(response.body.success).to.eq(false)
      expect(response.body.code).to.eq('CARD_NOT_FOUND')
      expect(response.body.message).to.eq('Kartu tidak terdaftar')
    })

    // Previously the bill stayed pending forever; now the console's rejection
    // reaches the cashier through the status endpoint.
    cy.contains('Kartu tidak terdaftar', { timeout: 15000 }).should('be.visible')
    cy.get('.reset-transaction').should('be.visible')
  })

  it('rejects a tap when no bill is waiting', () => {
    // Use a device that never had a bill so leftover pending rows from earlier
    // runs (which expire after 30s) cannot turn this into an EXPIRED answer.
    const cleanDeviceId = `ESP8266_CLEAN_${Date.now()}`

    cy.request('POST', '/api/device/register', {
      device_id: cleanDeviceId,
      firmware_version: '1.0.0',
    }).then((registered) => {
      cy.request({
        method: 'POST',
        url: `/api/device/${cleanDeviceId}/tap`,
        headers: { Authorization: `Bearer ${registered.body.device.api_token}` },
        body: { uid: CARD_UID },
        failOnStatusCode: false,
      }).then((response) => {
        expect(response.status).to.eq(400)
        expect(response.body.code).to.eq('NO_PENDING_TRANSACTION')
        expect(response.body.message).to.eq('Tidak ada transaksi menunggu')
      })
    })
  })

  it('keeps the token across reboots and records the heartbeat', () => {
    registerConsole()

    cy.get('@consoleToken').then((token) => {
      cy.request('POST', '/api/device/register', {
        device_id: DEVICE_ID,
        firmware_version: '1.0.0',
      }).then((second) => {
        expect(second.body.device.api_token).to.eq(token)
      })
    })

    consoleRequest('POST', '/heartbeat', {
      rssi: -57,
      free_heap: 28432,
      uptime: 120,
    }).then((response) => {
      expect(response.status).to.eq(200)
      expect(response.body.success).to.eq(true)
    })
  })
})
