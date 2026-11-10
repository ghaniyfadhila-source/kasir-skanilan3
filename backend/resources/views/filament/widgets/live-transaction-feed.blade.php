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
                            time: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
                            device: data.device_name ?? 'Unknown',
                            buyer: data.card?.user_name ?? 'Unknown',
                            total: data.total ?? 0,
                            uid: data.card?.uid ?? 'N/A',
                            status: data.status ?? 'success',
                        })
                        if (this.transactions.length > 50) {
                            this.transactions.pop()
                        }
                    })
                },
            }"
            class="nb-feed"
        >
            <template x-for="tx in transactions" :key="tx.id">
                <div class="nb-feed-item">
                    <div class="nb-feed-head">
                        <span class="nb-chip" :class="tx.status === 'success' ? 'nb-chip-green' : (tx.status === 'failed' ? 'nb-chip-red' : '')"
                            x-text="tx.status"></span>
                        <span class="nb-feed-total" x-text="'Rp ' + tx.total.toLocaleString('id-ID')"></span>
                    </div>
                    <div class="nb-feed-row">
                        <div><span>Device:</span> <span x-text="tx.device"></span></div>
                        <div><span>Pembeli:</span> <span x-text="tx.buyer"></span></div>
                        <div><span>UID:</span> <span x-text="tx.uid"></span></div>
                        <div><span>Waktu:</span> <span x-text="tx.time"></span></div>
                    </div>
                </div>
            </template>
            <div x-show="transactions.length === 0" class="nb-empty">Menunggu transaksi...</div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>