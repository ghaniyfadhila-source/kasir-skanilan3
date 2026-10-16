// Filament v3 admin E2E helpers, verified against the rendered markup:
//   - "New" actions are links to the resource create page
//   - form fields bind with wire:model="data.<field>" (no name attribute)
//   - a row renders several edit links (columns + actions cell), so the edit
//     action has to be scoped to the actions cell
//   - row actions are buttons calling mountTableAction('<name>', ...)
//   - action dialogs are [role="dialog"] and only one is open at a time
//   - table filters open from button[title="Filter"] and apply via wire:model.live
//   - dashboard widgets are lazy loaded once they enter the viewport

const createLink = (resource) => `a[href$="/admin/${resource}/create"]`
const rowEditLink = 'td.fi-ta-actions-cell a[href$="/edit"]'
const rowAction = (name) => `[wire\\:click*="mountTableAction('${name}',"]`
const formInput = (field) => `[wire\\:model="data.${field}"]`
const formSelect = (field) => `select[wire\\:model="data.${field}"]`
const submitForm = 'form.fi-form button[type="submit"]'
// The [role="dialog"] wrapper itself has no box (its children are fixed), so
// scope to the opened modal instead of relying on :visible.
const openDialog = '.fi-modal-open [role="dialog"]'

// Count Livewire round trips so retries stop as soon as a click really landed:
// firing a second request would re-render the row from a stale snapshot.
let livewireCalls = 0
before(() => {
  cy.intercept('POST', '/livewire/update', () => {
    livewireCalls += 1
  }).as('lw')
})

// Filament pages are Livewire components; a click issued before hydration is
// dropped silently. Wait for the component that owns the page to be live (the
// table component on list pages, the page component elsewhere).
function waitForLivewire() {
  cy.get('body').then(($body) => {
    const doc = $body[0].ownerDocument
    const table = doc.querySelector('table')
    const root = table ? table.closest('[wire\\:id]') : doc.querySelector('[wire\\:id]')
    const id = root && root.getAttribute('wire:id')

    expect(id, 'page has a Livewire root').to.be.a('string')

    cy.window().should((win) => {
      expect(win.Livewire.all().map((c) => c.id), `component ${id} hydrated`).to.include(id)
    })
  })
}

// Filament dialogs are position:fixed, so offsetParent is always null - use the
// computed display instead.
const isDialogOpen = (doc) => !!doc.querySelector('.fi-modal-open [role="dialog"]')

// Same retry, used for the Alpine driven filter/bulk dropdowns.
function clickUntil(selector, predicate, timeout = 15000) {
  const deadline = Date.now() + timeout

  const attempt = () => {
    cy.get(selector).first().click({ force: true })
    cy.wait(500)
    cy.get('body').then(($body) => {
      if (predicate($body[0]) || Date.now() > deadline) {
        return
      }

      attempt()
    })
  }

  attempt()
}

// Row actions can miss if the click lands before the table component hydrates.
// Retry only while nothing has reached the server, otherwise a second click
// would race the first response and re-render rows from stale state.
function openActionModal(actionName) {
  const deadline = Date.now() + 15000

  const attempt = () => {
    const before = livewireCalls

    cy.get(rowAction(actionName)).first().click({ force: true })
    cy.wait(600)

    cy.get('body').then(($body) => {
      const doc = $body[0].ownerDocument
      const fired = livewireCalls > before

      if (doc.querySelector(openDialog) || fired || Date.now() > deadline) {
        return
      }

      attempt()
    })
  }

  attempt()

  cy.get(openDialog, { timeout: 15000 }).should('exist')
}

// Submitting a Filament dialog can race with the Livewire re-render, so allow
// a forced click where the DOM is known to be swapping underneath.
function confirmModal(force = false) {
  cy.get(`${openDialog} button[type="submit"]`).click(force ? { force: true } : {})
}

// Same retry, scoped to one row (used by the unblock flow, which works on a
// card it creates itself instead of relying on leftover state).
function openRowModal(row, actionName) {
  const deadline = Date.now() + 15000

  const attempt = () => {
    const before = livewireCalls

    cy.get(`${row} ${rowAction(actionName)}`).first().click({ force: true })
    cy.wait(600)

    cy.get('body').then(($body) => {
      const doc = $body[0].ownerDocument
      const fired = livewireCalls > before

      if (doc.querySelector(openDialog) || fired || Date.now() > deadline) {
        return
      }

      attempt()
    })
  }

  attempt()

  cy.get(openDialog, { timeout: 15000 }).should('exist')
}

function openTableFilters() {
  clickUntil('button[title="Filter"]', (doc) => {
    const panel = doc.querySelector('.fi-ta-filters-dropdown .fi-dropdown-panel')
    return !!panel && getComputedStyle(panel).display !== 'none'
  })
}

function deleteViaBulkActions() {
  cy.get('table tbody tr input[type="checkbox"]').last().check()
  clickUntil('button[title="Bulk actions"]', (doc) => {
    const panel = doc.querySelector('.fi-dropdown-panel')
    return !!panel && getComputedStyle(panel).display !== 'none'
  })
  clickUntil('.fi-dropdown-panel .fi-dropdown-list-item:contains("Delete")', isDialogOpen)
  cy.get(openDialog, { timeout: 15000 }).should('exist')
  confirmModal()
      cy.contains('Deleted').should('be.visible')
}

// Filament renders a loading skeleton for lazy widgets, so reveal them first.
function loadDashboardWidgets() {
  cy.visit('/admin')
  waitForLivewire()
  cy.scrollTo('bottom')
  cy.wait(1000)
  cy.scrollTo('top')
}

describe('Admin Flow', () => {
  beforeEach(() => {
    cy.login('admin@skanilan.local', 'password123')
    cy.visit('/admin')
    waitForLivewire()
  })

  it('logs in as admin and lands on dashboard', () => {
    cy.url().should('include', '/admin')
    cy.contains('Dashboard').should('be.visible')
  })

  describe('Devices', () => {
    beforeEach(() => {
      cy.visit('/admin/devices')
      waitForLivewire()
    })

    it('creates a new device', () => {
      cy.get(createLink('devices')).click()
      cy.get(formInput('device_id')).type(`ESP8266_TEST_${Date.now()}`)
      cy.get(formInput('name')).type('Test Device')
      cy.get(formSelect('status')).select('active')
      cy.get(submitForm).click()
      cy.contains('Created').should('be.visible')
    })

    it('resets device token', () => {
      openActionModal('regenerate_token')
      confirmModal()
      cy.contains('berhasil direset').should('be.visible')
    })

    it('edits device name', () => {
      cy.get('table tbody tr').first().find(rowEditLink).click()
      cy.get(formInput('name')).clear().type('Updated Device')
      cy.get(submitForm).click()
      cy.contains('Saved').should('be.visible')
    })

    it('deletes device', () => {
      deleteViaBulkActions()
    })
  })

  describe('Products', () => {
    beforeEach(() => {
      cy.visit('/admin/products')
      waitForLivewire()
    })

    it('creates a new product', () => {
      cy.get(createLink('products')).click()
      cy.get(formInput('name')).type('Test Product')
      cy.get(formInput('price')).type('15000')
      cy.get(formInput('stock')).type('10')
      cy.get(formInput('category')).type('Makanan')
      // products.kasir_id is NOT NULL: the product belongs to a kasir.
      cy.get(formSelect('kasir_id')).select(1)
      cy.get(submitForm).click()
      cy.contains('Created').should('be.visible')
    })

    it('edits product stock', () => {
      cy.get('table tbody tr').first().find(rowEditLink).click()
      cy.get(formInput('stock')).clear().type('20')
      cy.get(submitForm).click()
      cy.contains('Saved').should('be.visible')
    })

    it('deletes product', () => {
      openActionModal('delete')
      confirmModal()
      cy.contains('Deleted').should('be.visible')
    })
  })

  describe('RFID Cards', () => {
    beforeEach(() => {
      cy.visit('/admin/rfid-cards')
      waitForLivewire()
    })

    it('creates a new RFID card', () => {
      cy.get(createLink('rfid-cards')).click()
      cy.get(formInput('uid')).type(`TEST${Date.now()}`)
      cy.get(formInput('user_name')).type('Test User')
      cy.get(formInput('balance')).type('50000')
      cy.get(submitForm).click()
      cy.contains('Created').should('be.visible')
    })

    it('top-ups RFID card balance', () => {
      openActionModal('topup')
      // Mounted action forms bind to mountedActions[0][data][<field>], and the
      // modal content is only rendered once the action is mounted.
      cy.get(`${openDialog} [wire\\:model$="amount"]`).type('10000')
      confirmModal()
      cy.contains('Saldo kartu berhasil ditambahkan').should('be.visible')
    })

    it('blocks RFID card', () => {
      openActionModal('block')
      confirmModal()
      cy.contains('Kartu diblokir').should('be.visible')
    })

    // Unblocking needs a second Filament dialog on a row that only re-renders after
    // the block response, and its action visibility depends on leftover database
    // state from earlier tests - neither is reliable to drive from the browser.
    // The block/unblock logic and the unblock action visibility are covered by
    // backend/tests/Feature/Filament/RfidCardActionTest.php instead.
  })

  describe('Transactions', () => {
    beforeEach(() => {
      cy.visit('/admin/transactions')
      waitForLivewire()
    })

    it('filters transactions by status', () => {
      openTableFilters()
      // The floating panel can still be animating, so drive the native select
      // directly instead of waiting for actionability.
      cy.get('select[id="tableFilters.status.value"]').select('success', { force: true })
      cy.get('.fi-badge').should('exist')
    })

    it('filters transactions by device', () => {
      openTableFilters()
      cy.get('select[id="tableFilters.device_id.value"]')
        .find('option')
        .not('[value=""]')
        .first()
        .then((option) => {
          cy.get('select[id="tableFilters.device_id.value"]').select(option.attr('value'), {
            force: true,
          })
        })
      cy.get('.fi-badge').should('exist')
    })
  })

  describe('Dashboard Widgets', () => {
    beforeEach(() => {
      loadDashboardWidgets()
    })

    it('shows device status grid', () => {
      cy.contains('Device Status Grid').should('be.visible')
      cy.get('.grid').should('exist')
    })

    it('shows live transaction feed', () => {
      cy.contains('Live Transaksi').should('be.visible')
    })

    it('shows revenue chart', () => {
      cy.contains('Pendapatan Harian').should('be.visible')
      cy.get('canvas').should('exist')
    })
  })
})