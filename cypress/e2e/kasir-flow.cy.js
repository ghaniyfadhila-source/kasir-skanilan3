describe('Kasir POS Flow', () => {
  beforeEach(() => {
    cy.loginKasir('kasir@skanilan.local', 'password123')
    cy.visit('/kasir/pos')
  })

  it('logs in as kasir and lands on POS page', () => {
    cy.url().should('include', '/kasir/pos')
    cy.contains('Kasir POS').should('be.visible')
    cy.get('.product-grid').should('exist')
  })

  describe('Cart operations', () => {
    it('adds products to cart', () => {
      cy.get('.product-card').first().click()
      cy.get('.product-card').eq(1).click()

      cy.get('.cart-item').should('have.length', 2)
      cy.get('.cart-total').should('contain', 'Rp')
    })

    it('increments quantity', () => {
      cy.get('.product-card').first().click()
      cy.get('.cart-item').first().within(() => {
        cy.get('button[wire\\:click*="increment"]').click()
        cy.get('.qty').should('contain', '2')
      })
    })

    it('decrements quantity', () => {
      cy.get('.product-card').first().click()
      cy.get('.product-card').first().click()
      cy.get('.cart-item').first().within(() => {
        cy.get('button[wire\\:click*="decrement"]').click()
        cy.get('.qty').should('contain', '1')
      })
    })

    it('removes item from cart', () => {
      cy.get('.product-card').first().click()
      cy.get('.cart-item').first().within(() => {
        cy.get('button[wire\\:click*="remove"]').click()
      })
      cy.get('.cart-item').should('not.exist')
    })

    it('clears cart', () => {
      cy.get('.product-card').first().click()
      cy.get('.product-card').eq(1).click()
      cy.get('button[wire\\:click="clearCart"]').click()
      cy.get('.cart-item').should('not.exist')
    })
  })

  describe('Checkout flow', () => {
    beforeEach(() => {
      cy.get('.product-card').first().click()
      cy.get('.product-card').eq(1).click()
    })

    it('starts checkout and shows waiting for tap', () => {
      cy.get('button[wire\\:click="checkout"]').click()
      cy.contains('Menunggu pembeli tap kartu').should('be.visible')
      cy.get('.spinner').should('be.visible')
    })

    it('processes tap successfully (mocked)', () => {
      cy.get('button[wire\\:click="checkout"]').click()
      cy.contains('Menunggu pembeli tap kartu').should('be.visible')

      cy.intercept('GET', '/api/transactions/*/status', {
        statusCode: 200,
        body: {
          success: true,
          transaction: {
            id: 1,
            status: 'success',
            total: 30000,
            balance_before: 50000,
            balance_after: 20000,
            card: {
              uid: 'A1B2C3D4',
              user_name: 'Budi Santoso'
            }
          }
        }
      }).as('statusCheck')

      cy.wait('@statusCheck', { timeout: 10000 })

      cy.contains('Transaksi Berhasil').should('be.visible')
      cy.contains('Budi Santoso').should('be.visible')
      cy.contains('A1B2C3D4').should('be.visible')
      cy.contains('Rp 50.000').should('be.visible')
      cy.contains('Rp 20.000').should('be.visible')
      cy.contains('Rp 30.000').should('be.visible')
    })

    it('shows failed state on insufficient balance (mocked)', () => {
      cy.get('button[wire\\:click="checkout"]').click()
      cy.contains('Menunggu pembeli tap kartu').should('be.visible')

      cy.intercept('GET', '/api/transactions/*/status', {
        statusCode: 200,
        body: {
          success: false,
          code: 'INSUFFICIENT_BALANCE',
          message: 'Saldo tidak mencukupi',
          balance: 10000,
          required: 30000
        }
      }).as('statusCheckFailed')

      cy.wait('@statusCheckFailed', { timeout: 10000 })

      cy.contains('Transaksi Gagal').should('be.visible')
      cy.contains('Saldo tidak mencukupi').should('be.visible')
      cy.get('button[wire\\:click="reset"]').should('be.visible')
    })

    it('shows failed state on card not found (mocked)', () => {
      cy.get('button[wire\\:click="checkout"]').click()
      cy.contains('Menunggu pembeli tap kartu').should('be.visible')

      cy.intercept('GET', '/api/transactions/*/status', {
        statusCode: 200,
        body: {
          success: false,
          code: 'CARD_NOT_FOUND',
          message: 'Kartu tidak terdaftar'
        }
      }).as('statusCheckCardNotFound')

      cy.wait('@statusCheckCardNotFound', { timeout: 10000 })

      cy.contains('Transaksi Gagal').should('be.visible')
      cy.contains('Kartu tidak terdaftar').should('be.visible')
    })

    it('shows timeout state (mocked)', () => {
      cy.get('button[wire\\:click="checkout"]').click()
      cy.contains('Menunggu pembeli tap kartu').should('be.visible')

      cy.intercept('GET', '/api/transactions/*/status', {
        statusCode: 200,
        body: {
          success: false,
          code: 'EXPIRED',
          message: 'Transaksi kadaluarsa'
        }
      }).as('statusCheckExpired')

      cy.wait('@statusCheckExpired', { timeout: 10000 })

      cy.contains('Transaksi Gagal').should('be.visible')
      cy.contains('Waktu Habis').should('be.visible')
    })

    it('cancels transaction manually', () => {
      cy.get('button[wire\\:click="checkout"]').click()
      cy.contains('Menunggu pembeli tap kartu').should('be.visible')

      cy.get('button[wire\\:click="cancel"]').click()
      cy.contains('Transaksi dibatalkan').should('be.visible')
      cy.get('button[wire\\:click="reset"]').should('be.visible')
    })
  })

  describe('Admin transaction feed verification', () => {
    it('transaction appears in admin feed after success', () => {
      cy.get('.product-card').first().click()
      cy.get('button[wire\\:click="checkout"]').click()

      cy.intercept('GET', '/api/transactions/*/status', {
        statusCode: 200,
        body: {
          success: true,
          transaction: {
            id: 999,
            status: 'success',
            total: 15000,
            balance_before: 50000,
            balance_after: 35000,
            card: {
              uid: 'A1B2C3D4',
              user_name: 'Budi Santoso'
            }
          }
        }
      }).as('statusCheckAdmin')

      cy.wait('@statusCheckAdmin', { timeout: 10000 })

      cy.login('admin@skanilan.local', 'password123')
      cy.visit('/admin')
      cy.contains('Budi Santoso').should('be.visible')
      cy.contains('A1B2C3D4').should('be.visible')
      cy.contains('Rp 15.000').should('be.visible')
    })
  })
})