<x-filament-widgets::widget>
    <x-filament::section
        :heading="$this->getHeading()"
        :description="$this->getDescription()"
    >
        <div
            wire:ignore
            x-data="{
        transactions: [],
        init() {
            this.$wire.on('transaction-completed', (data) => {
                this.transactions.unshift({
                    id: data.id ?? Date.now(),
                    time: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
                    device: data.device_name ?? 'Unknown',
                    buyer: data.card?.user_name ?? 'Unknown',
                    total: data.total ?? 0,
                    uid: data.card?.uid ?? 'N/A',
                    status: data.status ?? 'success',
                    visible: true,
                })

                if (this.transactions.length > 50) {
                    this.transactions.pop()
                }
            })
        },
    }"
    class="space-y-2 max-h-96 overflow-y-auto"
>
    <template x-for="tx in transactions" :key="tx.id">
        <div class="bg-white rounded-lg border p-4 shadow-sm transition-all duration-300" x-show="tx.visible" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-900" x-text="tx.time"></span>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                    :class="tx.status === 'success' ? 'bg-green-100 text-green-800' : (tx.status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800')"
                    x-text="tx.status"></span>
            </div>
            <div class="grid grid-cols-2 gap-1 text-sm text-gray-600">
                <div><span class="font-medium">Device:</span> <span x-text="tx.device"></span></div>
                <div><span class="font-medium">Pembeli:</span> <span x-text="tx.buyer"></span></div>
                <div><span class="font-medium">Total:</span> <span class="font-semibold text-amber-600" x-text="'Rp ' + tx.total.toLocaleString('id-ID')"></span></div>
                <div><span class="font-medium">UID:</span> <span class="font-mono" x-text="tx.uid"></span></div>
            </div>
        </div>
    </template>

    <div x-show="transactions.length === 0" class="text-center py-8 text-gray-500">
        <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
        </svg>
        <p class="mt-2">Menunggu transaksi...</p>
        </div>
    </div>
    </x-filament::section>
</x-filament-widgets::widget>