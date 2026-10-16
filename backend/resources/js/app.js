// App JS
import './bootstrap'

// Livewire is loaded via @livewireScripts in the layout.
//
// The kasir POS polls the transaction status endpoint from the browser. This
// lives in the bundle (not inline in a Blade view) so it is registered exactly
// once per page load - Blade scripts injected by Livewire morphs are not
// guaranteed to run.
window.kasirPolling = {
    interval: null,

    start(transactionId) {
        this.stop()

        if (!transactionId) {
            return
        }

        const component = document.querySelector('[wire\\:id]')

        if (!component) {
            return
        }

        const id = component.getAttribute('wire:id')

        this.interval = setInterval(async () => {
            try {
                const response = await fetch(`/api/transactions/${transactionId}/status`, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
                const data = await response.json()

                if (data.success && data.transaction?.status !== 'pending') {
                    this.stop()

                    const livewire = window.Livewire?.find(id)

                    livewire?.call('handlePollResult', data.transaction)
                }
            } catch (error) {
                console.error('Poll error:', error)
            }
        }, 2000)
    },

    stop() {
        if (this.interval) {
            clearInterval(this.interval)
        }

        this.interval = null
    },
}

document.addEventListener('livewire:init', () => {
    // Emitted by KasirPos after a successful transaction, so the cashier gets a
    // clean screen for the next buyer.
    window.addEventListener('auto-reset', () => {
        setTimeout(() => window.location.reload(), 3000)
    })
})

console.log('Kasir Cashless app loaded')