// ***********************************************************
// This example support/e2e.js is processed and
// loaded automatically before your test files.
//
// This is a great place to put global configuration and
// behavior that modifies Cypress.
//
// You can change the location of this file or turn off
// automatically serving support files with the
// 'supportFile' configuration option.
//
// You can read more here:
// https://on.cypress.io/configuration
// ***********************************************************

// Import commands.js using ES2015 syntax:
import './commands'

// Filament registers the Alpine `sidebar` store in its own bundle. On a slow
// runner Alpine can evaluate the persisted sidebar binding before that store
// exists, which throws a TypeError inside Livewire's reactive effect. The page
// recovers as soon as the bundle loads, so ignore only this exact race instead
// of letting it fail the whole spec.
Cypress.on('uncaught:exception', (err) => {
  const stack = err.stack || ''
  if (stack.includes('$store.sidebar') || err.message.includes('$store.sidebar.isOpen')) {
    return false
  }
})