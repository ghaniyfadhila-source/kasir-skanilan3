// App JS
import './bootstrap'

// Livewire is loaded via @livewireScripts in the layout.
//
// POS polling now lives in the KasirPos Livewire component's @script block, so
// it always targets the component that owns the transaction instead of the
// first [wire:id] on the page. This bundle no longer needs POS-specific code.

console.log('Kasir Cashless app loaded')
