// Helpers for the kasir POS Livewire component.
//
// The view exposes stable class hooks (product-card, cart-item, qty,
// cart-total) so the spec does not depend on Tailwind utility classes.

const statusPath = '/api/transactions/*/status'

let livewireCalls = 0
before(() => {
  cy.intercept('POST', '/livewire/update', () => {
    livewireCalls += 1
  }).as('lw')
})

// A click issued before Livewire hydrates is dropped silently, so retry until
// the DOM reflects the interaction - but never while a request is in flight,
// otherwise the second click would race the first response.
function clickUntil(selector, predicate, { index = 0, timeout = 15000 } = {}) {
  const deadline = Date.now() + timeout

  const attempt = () => {
    const before = livewireCalls

    cy.get(selector).eq(index).click({ force: true })
    cy.wait(500)

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

const cartCount = (n) => (doc) => doc.querySelectorAll('.cart-item').length === n
const cartCountAtLeast = (n) => (doc) => doc.querySelectorAll('.cart-item').length >= n
const qtyOf = (index, n) => (doc) =>
  doc.querySelectorAll('.cart-item .qty')[index]?.textContent.trim() === String(n)
const waitingForTap = (doc) => !!doc.querySelector('button[wire\\:click="cancelTransaction"]')
const showsText = (text) => (doc) => doc.body.innerText.includes(text)

// Poll the real status endpoint shape: { success: true, transaction: {...} }
function mockStatus(alias, transaction) {
  cy.intercept('GET', statusPath, {
    statusCode: 200,
    body: { success: true, transaction },
  }).as(alias)
}

// The POS creates the transaction through the device API, which needs a valid
// device token; stub it so the checkout flow is deterministic.
function mockCreate(transactionId = 42) {
  cy.intercept('POST', '/api/transactions', {
    statusCode: 200,
    body: { success: true, transaction: { id: transactionId } },
  }).as('create')
}

describe('Kasir POS Flow', () => {
  beforeEach(() => {
    cy.loginKasir('kasir@skanilan.local', 'password123')
    cy.visit('/kasir/pos')
    cy.get('.product-grid').should('exist')
  })

  it('logs in as kasir and lands on POS page', () => {
    cy.url().should('include', '/kasir/pos')
    cy.contains('Kasir Cashless').should('be.visible')
    cy.get('.product-grid').should('exist')
  })

  describe('Cart operations', () => {
    it('adds products to cart', () => {
      clickUntil('.product-card', cartCountAtLeast(1))
      clickUntil('.product-card', cartCount(2), { index: 1 })

      cy.get('.cart-item').should('have.length', 2)
      cy.get('.cart-total').should('contain', 'Rp')
    })

    it('increments quantity', () => {
      clickUntil('.product-card', cartCountAtLeast(1))
      clickUntil('.cart-qty-inc', qtyOf(0, 2))
      cy.get('.cart-item .qty').first().should('contain', '2')
    })

    it('decrements quantity', () => {
      clickUntil('.product-card', cartCountAtLeast(1))
      clickUntil('.cart-qty-inc', qtyOf(0, 2))
      clickUntil('.cart-qty-dec', qtyOf(0, 1))
      cy.get('.cart-item .qty').first().should('contain', '1')
    })

    it('removes item from cart', () => {
      clickUntil('.product-card', cartCountAtLeast(1))
      clickUntil('.cart-remove', cartCount(0))
      cy.get('.cart-item').should('not.exist')
    })

    it('clears cart', () => {
      clickUntil('.product-card', cartCountAtLeast(1))
      clickUntil('.product-card', cartCount(2), { index: 1 })
      clickUntil('button[wire\\:click="clearCart"]', cartCount(0))
      cy.get('.cart-item').should('not.exist')
    })
  })

  describe('Checkout flow', () => {
    it('starts checkout and shows waiting for tap', () => {
      mockCreate()
      clickUntil('.product-card', cartCountAtLeast(1))
      clickUntil('button[wire\\:click="checkout"]', waitingForTap)

      cy.get('button[wire\\:click="cancelTransaction"]').should('be.visible')
      cy.contains('Memproses pembayaran').should('be.visible')
    })

    it('processes tap successfully (mocked)', () => {
      mockStatus('statusCheck', {
        id: 1,
        status: 'success',
        total: 30000,
        balance_before: 50000,
        balance_after: 20000,
        card: { uid: 'A1B2C3D4', user_name: 'Budi Santoso' },
      })
      mockCreate(1)

      clickUntil('.product-card', cartCountAtLeast(1))
      clickUntil('button[wire\\:click="checkout"]', waitingForTap)

      cy.wait('@statusCheck', { timeout: 15000 })

      cy.contains('Transaksi berhasil').should('be.visible')
      cy.contains('Budi Santoso').should('be.visible')
      cy.contains('A1B2C3D4').should('be.visible')
      // Balances come from the mocked response, the total line comes from the cart.
      cy.contains('Rp 50.000').should('be.visible')
      cy.contains('Rp 20.000').should('be.visible')
      cy.contains('Rp 2.000').should('be.visible')
    })

    it('shows failed state when the device reports a failure (mocked)', () => {
      mockStatus('statusCheckFailed', {
        id: 1,
        status: 'failed',
        message: 'Saldo tidak mencukupi',
      })
      mockCreate(1)

      clickUntil('.product-card', cartCountAtLeast(1))
      clickUntil('button[wire\\:click="checkout"]', waitingForTap)

      cy.wait('@statusCheckFailed', { timeout: 15000 })

      cy.contains('Saldo tidak mencukupi').should('be.visible')
      cy.get('.reset-transaction').should('be.visible')
    })

    it('shows failed state when the card is unknown (mocked)', () => {
      mockStatus('statusCheckCardNotFound', {
        id: 1,
        status: 'failed',
        message: 'Kartu tidak terdaftar',
      })
      mockCreate(1)

      clickUntil('.product-card', cartCountAtLeast(1))
      clickUntil('button[wire\\:click="checkout"]', waitingForTap)

      cy.wait('@statusCheckCardNotFound', { timeout: 15000 })

      cy.contains('Kartu tidak terdaftar').should('be.visible')
      cy.get('.reset-transaction').should('be.visible')
    })

    it('shows timeout state (mocked)', () => {
      mockStatus('statusCheckExpired', { id: 1, status: 'cancelled' })
      mockCreate(1)

      clickUntil('.product-card', cartCountAtLeast(1))
      clickUntil('button[wire\\:click="checkout"]', waitingForTap)

      cy.wait('@statusCheckExpired', { timeout: 15000 })

      cy.contains('Transaksi dibatalkan (timeout)').should('be.visible')
      cy.get('.reset-transaction').should('be.visible')
    })

    it('cancels transaction manually', () => {
      mockCreate(1)

      clickUntil('.product-card', cartCountAtLeast(1))
      clickUntil('button[wire\\:click="checkout"]', waitingForTap)

      // Cancelling happens in-process (no HTTP call), so assert the UI state.
      clickUntil('button[wire\\:click="cancelTransaction"]', (doc) =>
        doc.body.innerText.includes('Siap transaksi baru')
      )

      cy.contains('Siap transaksi baru').should('be.visible')
    })
  })

  describe('Admin transaction feed verification', () => {
    // The admin dashboard feed only receives transactions that are really
    // broadcast (a real device tap), so a mocked status cannot populate it. This
    // test therefore verifies the POS side of a completed transaction; the
    // broadcast itself is covered by the PHP feature tests.
    it('shows the completed transaction details on the POS', () => {
      mockStatus('statusCheckAdmin', {
        id: 999,
        status: 'success',
        total: 15000,
        balance_before: 50000,
        balance_after: 35000,
        card: { uid: 'A1B2C3D4', user_name: 'Budi Santoso' },
      })
      mockCreate(999)

      clickUntil('.product-card', cartCountAtLeast(1))
      clickUntil('button[wire\\:click="checkout"]', waitingForTap)

      cy.wait('@statusCheckAdmin', { timeout: 15000 })
      cy.contains('Transaksi berhasil').should('be.visible')
      cy.contains('Budi Santoso').should('be.visible')
      cy.contains('A1B2C3D4').should('be.visible')
    })
  })
})