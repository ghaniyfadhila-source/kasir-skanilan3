Cypress.Commands.add('login', (email, password) => {
  cy.session([email, password], () => {
    cy.visit('/admin/login')
    cy.get('input[type="email"]').type(email)
    cy.get('input[type="password"]').type(password)
    cy.get('button[type="submit"]').click()
    cy.location('pathname').should('eq', '/admin')
  })
})

Cypress.Commands.add('loginKasir', (email, password) => {
  cy.session([email, password], () => {
    cy.visit('/admin/login')
    cy.get('input[type="email"]').type(email)
    cy.get('input[type="password"]').type(password)
    cy.get('button[type="submit"]').click()
    cy.location('pathname').should('eq', '/admin/pos')
  })
})
