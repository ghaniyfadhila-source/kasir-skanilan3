describe('Admin Flow', () => {
  beforeEach(() => {
    cy.login('admin@skanilan.local', 'password123')
    cy.visit('/admin')
  })

  it('logs in as admin and lands on dashboard', () => {
    cy.url().should('include', '/admin')
    cy.contains('Dashboard').should('be.visible')
  })

  describe('Devices', () => {
    beforeEach(() => {
      cy.visit('/admin/devices')
    })

    it('creates a new device', () => {
      cy.get('[wire\\:click="create"]').click()
      cy.get('input[name="device_id"]').type(`ESP8266_TEST_${Date.now()}`)
      cy.get('input[name="name"]').type('Test Device')
      cy.get('select[name="status"]').select('active')
      cy.get('select[name="kasir_id"]').select('Kasir Demo')
      cy.get('button[type="submit"]').click()
      cy.contains('Device created').should('be.visible')
    })

    it('resets device token', () => {
      cy.get('table tbody tr').first().within(() => {
        cy.get('button[wire\\:click*="resetToken"]').click()
      })
      cy.contains('Token reset').should('be.visible')
    })

    it('edits device name', () => {
      cy.get('table tbody tr').first().within(() => {
        cy.get('button[wire\\:click*="edit"]').click()
      })
      cy.get('input[name="name"]').clear().type('Updated Device')
      cy.get('button[type="submit"]').click()
      cy.contains('Device updated').should('be.visible')
    })

    it('deletes device', () => {
      cy.get('table tbody tr').last().within(() => {
        cy.get('button[wire\\:click*="delete"]').click()
      })
      cy.get('.fi-modal').should('be.visible')
      cy.get('.fi-modal button[type="submit"]').click()
      cy.contains('Device deleted').should('be.visible')
    })
  })

  describe('Products', () => {
    beforeEach(() => {
      cy.visit('/admin/products')
    })

    it('creates a new product', () => {
      cy.get('[wire\\:click="create"]').click()
      cy.get('input[name="name"]').type('Test Product')
      cy.get('input[name="price"]').type('15000')
      cy.get('input[name="stock"]').type('10')
      cy.get('input[name="category"]').type('Makanan')
      cy.get('input[name="is_active"]').check()
      cy.get('button[type="submit"]').click()
      cy.contains('Product created').should('be.visible')
    })

    it('edits product stock', () => {
      cy.get('table tbody tr').first().within(() => {
        cy.get('button[wire\\:click*="edit"]').click()
      })
      cy.get('input[name="stock"]').clear().type('20')
      cy.get('button[type="submit"]').click()
      cy.contains('Product updated').should('be.visible')
    })

    it('deletes product', () => {
      cy.get('table tbody tr').last().within(() => {
        cy.get('button[wire\\:click*="delete"]').click()
      })
      cy.get('.fi-modal').should('be.visible')
      cy.get('.fi-modal button[type="submit"]').click()
      cy.contains('Product deleted').should('be.visible')
    })
  })

  describe('RFID Cards', () => {
    beforeEach(() => {
      cy.visit('/admin/rfid-cards')
    })

    it('creates a new RFID card', () => {
      cy.get('[wire\\:click="create"]').click()
      cy.get('input[name="uid"]').type(`TEST${Date.now()}`)
      cy.get('input[name="user_name"]').type('Test User')
      cy.get('input[name="balance"]').type('50000')
      cy.get('input[name="is_active"]').check()
      cy.get('button[type="submit"]').click()
      cy.contains('Rfid card created').should('be.visible')
    })

    it('top-ups RFID card balance', () => {
      cy.get('table tbody tr').first().within(() => {
        cy.get('button[wire\\:click*="topUp"]').click()
      })
      cy.get('.fi-modal').should('be.visible')
      cy.get('input[name="amount"]').type('10000')
      cy.get('.fi-modal button[type="submit"]').click()
      cy.contains('Balance topped up').should('be.visible')
    })

    it('blocks RFID card', () => {
      cy.get('table tbody tr').first().within(() => {
        cy.get('button[wire\\:click*="block"]').click()
      })
      cy.get('.fi-modal').should('be.visible')
      cy.get('.fi-modal button[type="submit"]').click()
      cy.contains('Card blocked').should('be.visible')
    })

    it('unblocks RFID card', () => {
      cy.get('table tbody tr').first().within(() => {
        cy.get('button[wire\\:click*="activate"]').click()
      })
      cy.get('.fi-modal').should('be.visible')
      cy.get('.fi-modal button[type="submit"]').click()
      cy.contains('Card activated').should('be.visible')
    })
  })

  describe('Transactions', () => {
    beforeEach(() => {
      cy.visit('/admin/transactions')
    })

    it('filters transactions by status', () => {
      cy.get('select[name="status"]').select('success')
      cy.get('button[type="submit"]').click()
      cy.get('table tbody tr').should('be.visible')
    })

    it('filters transactions by device', () => {
      cy.get('select[name="device_id"]').select('ESP8266_DEMO_001')
      cy.get('button[type="submit"]').click()
      cy.get('table tbody tr').should('be.visible')
    })
  })

  describe('Dashboard Widgets', () => {
    beforeEach(() => {
      cy.visit('/admin')
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