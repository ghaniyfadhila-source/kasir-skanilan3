<div wire:ignore class="space-y-2 max-h-96 overflow-y-auto" id="live-transaction-feed">
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

<script>
document.addEventListener('livewire:load', () => {
    Livewire.on('transaction-completed', (data) => {
        const feed = document.getElementById('live-transaction-feed');
        if (!feed) return;

        const now = new Date();
        const time = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

        const item = {
            id: data.id,
            time,
            device: data.device_name || 'Unknown',
            buyer: data.card?.user_name || 'Unknown',
            total: data.total || 0,
            uid: data.card?.uid || 'N/A',
            status: data.status || 'success',
            visible: true,
        };

        // Add to Alpine.js data
        const alpineComponent = feed.__x;
        if (alpineComponent) {
            alpineComponent.$data.transactions.unshift(item);
            if (alpineComponent.$data.transactions.length > 50) {
                alpineComponent.$data.transactions.pop();
            }
        }
    });
});
</script>

@push('scripts')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endpush