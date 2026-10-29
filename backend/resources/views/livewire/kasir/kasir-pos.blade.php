@php($device = auth()->user()->device)

<div @class(['kasir-pos', 'kasir-pos-embedded' => $embedded, 'min-h-screen bg-gray-50' => ! $embedded]) data-kasir-pos>
    @if ($embedded)
        <div class="pos-console-bar">
            <span @class(['pos-console-dot', 'is-online' => $device?->isOnline()])></span>
            <span class="pos-console-name">{{ $device?->name ?? 'Console belum tertaut' }}</span>
            <span @class(['pos-console-state', 'is-online' => $device?->isOnline()])>
                {{ $device?->isOnline() ? 'Online' : 'Offline' }}
            </span>
            @if (! $device?->isOnline())
                <span class="pos-console-hint">Transaksi akan menunggu console online.</span>
            @endif
        </div>
    @else
        <header class="bg-white shadow-sm border-b">
            <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
                <h1 class="text-xl font-bold text-gray-900">Kasir Cashless</h1>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-600">{{ auth()->user()->name }}</span>
                    <span class="inline-flex items-center gap-2 px-2 py-1 rounded-full text-xs {{ $device?->isOnline() ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                        <span class="h-2 w-2 rounded-full {{ $device?->isOnline() ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                        {{ $device?->name ?? 'No Device' }}
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="logout-button text-sm text-gray-500 hover:text-red-600">Keluar</button>
                    </form>
                </div>
            </div>
        </header>
    @endif

    <div @class(['pos-content' => $embedded, 'max-w-4xl mx-auto px-4 py-6' => ! $embedded])>
        <div @class(['pos-grid' => $embedded, 'grid grid-cols-1 lg:grid-cols-3 gap-6' => ! $embedded])>
            <!-- Product List -->
            <div @class(['pos-main' => $embedded, 'lg:col-span-2' => ! $embedded])>
                <div @class(['pos-card' => $embedded, 'bg-white rounded-lg shadow p-4' => ! $embedded])>
                    <h2 class="pos-card-title">Menu</h2>

                    @if(empty($products))
                        <p @class(['pos-empty' => $embedded, 'text-gray-500 text-center py-8' => ! $embedded])>Belum ada produk. Tambahkan di panel admin.</p>
                    @else
                        <div @class(['product-grid pos-product-grid' => $embedded, 'product-grid grid grid-cols-2 sm:grid-cols-3 gap-3' => ! $embedded])>
                            @foreach($products as $product)
                                <button
                                    wire:click="addToCart({{ $product['id'] }})"
                                    @class(['product-card pos-product-card' => $embedded, 'product-card p-4 border rounded-lg hover:bg-gray-50 transition text-left group' => ! $embedded])
                                    {{ $product['stock'] <= 0 ? 'disabled' : '' }}
                                >
                                    <div class="font-medium text-gray-900">{{ $product['name'] }}</div>
                                    <div class="text-sm text-gray-500">{{ $product['category'] ?? '' }}</div>
                                    <div @class(['pos-price' => $embedded, 'text-lg font-bold text-green-600 mt-1' => ! $embedded])>Rp {{ number_format($product['price'], 0, ',', '.') }}</div>
                                    <div class="text-xs text-gray-400 mt-1">Stok: {{ $product['stock'] }}</div>
                                    @if($product['stock'] <= 0)
                                        <div @class(['pos-danger-text text-xs mt-1' => $embedded, 'text-xs text-red-500 mt-1' => ! $embedded])>Habis</div>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Cart & Transaction -->
            <div @class(['pos-side' => $embedded, 'lg:col-span-1' => ! $embedded])>
                <!-- Cart -->
                <div @class(['pos-card' => $embedded, 'bg-white rounded-lg shadow p-4 mb-4' => ! $embedded])>
                    <h2 class="pos-card-title flex justify-between items-center">
                        Keranjang
                        @if(!empty($cart))
                            <span class="text-sm text-gray-500">{{ count($cart) }} item</span>
                            <button wire:click="clearCart" class="ml-auto text-xs text-red-500 hover:text-red-700">Kosongkan</button>
                        @endif
                    </h2>

                    @if(empty($cart))
                        <p @class(['pos-empty' => $embedded, 'text-gray-500 text-center py-8' => ! $embedded])>Keranjang kosong</p>
                    @else
                        <div @class(['pos-cart-list' => $embedded, 'space-y-3 max-h-64 overflow-y-auto' => ! $embedded])>
                            @foreach($cart as $index => $item)
                                <div @class(['cart-item pos-cart-item' => $embedded, 'cart-item flex items-center justify-between p-2 bg-gray-50 rounded' => ! $embedded])>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium text-sm truncate">{{ $item['name'] }}</div>
                                        <div class="text-xs text-gray-500">Rp {{ number_format($item['price'], 0, ',', '.') }} x {{ $item['qty'] }}</div>
                                    </div>
                                    <div class="flex items-center gap-2 ml-2">
                                        <button wire:click="updateQty({{ $index }}, {{ $item['qty'] - 1 }})" @class(['cart-qty-dec pos-qty-btn' => $embedded, 'cart-qty-dec w-6 h-6 rounded border text-xs hover:bg-gray-100' => ! $embedded])>-</button>
                                        <span class="qty w-8 text-center text-sm">{{ $item['qty'] }}</span>
                                        <button wire:click="updateQty({{ $index }}, {{ $item['qty'] + 1 }})" @class(['cart-qty-inc pos-qty-btn' => $embedded, 'cart-qty-inc w-6 h-6 rounded border text-xs hover:bg-gray-100' => ! $embedded])>+</button>
                                        <button wire:click="removeFromCart({{ $index }})" class="cart-remove ml-2 text-red-500 hover:text-red-700 text-xs">✕</button>
                                    </div>
                                    <div class="text-right ml-2">
                                        <div class="font-semibold text-sm">Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div @class(['pos-total-row' => $embedded, 'border-t mt-4 pt-4' => ! $embedded])>
                            <span>Total</span>
                            <span class="cart-total">Rp {{ number_format($total, 0, ',', '.') }}</span>
                        </div>

                        <button
                            wire:click="checkout"
                            @class(['pos-btn-primary' => $embedded, 'w-full mt-4 bg-green-600 text-white py-3 rounded-lg font-semibold hover:bg-green-700 transition disabled:opacity-50' => ! $embedded])
                            {{ $status !== 'idle' ? 'disabled' : '' }}
                        >
                            {{ $status === 'idle' ? 'Bayar (RFID)' : 'Memproses...' }}
                        </button>

                        @if($status === 'pending' || $status === 'processing')
                            <button
                                wire:click="cancelTransaction"
                                @class(['pos-btn-secondary' => $embedded, 'w-full mt-2 bg-gray-200 text-gray-700 py-2 rounded-lg font-medium hover:bg-gray-300 transition' => ! $embedded])
                            >
                                Batalkan
                            </button>
                        @endif
                    @endif
                </div>

                <!-- Transaction Status -->
                <div @class(['pos-card pos-status-card' => $embedded, 'bg-white rounded-lg shadow p-4' => ! $embedded])>
                    <h2 class="pos-card-title">Status Transaksi</h2>

                    @if($status === 'idle')
                        <div @class(['pos-empty' => $embedded, 'text-center py-8 text-gray-500' => ! $embedded])>
                            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                            </svg>
                            <p class="mt-2">Siap transaksi baru</p>
                        </div>

                    @elseif($status === 'pending')
                        <div class="text-center py-8">
                            <div @class(['pos-spinner' => $embedded, 'animate-spin rounded-full h-12 w-12 border-4 border-green-500 border-t-transparent mx-auto' => ! $embedded])></div>
                            <p @class(['mt-3' => ! $embedded, 'pos-muted mt-3' => $embedded])>{{ $message }}</p>
                            <p @class(['mt-1 text-sm' => ! $embedded, 'pos-muted mt-1' => $embedded])>Menunggu pembeli tap kartu...</p>
                        </div>

                    @elseif($status === 'processing')
                        <div class="text-center py-8">
                            <div @class(['pos-spinner pos-spinner-blue' => $embedded, 'animate-spin rounded-full h-12 w-12 border-4 border-blue-500 border-t-transparent mx-auto' => ! $embedded])></div>
                            <p @class(['mt-3' => ! $embedded, 'pos-muted mt-3' => $embedded])>Memproses pembayaran...</p>
                            <p @class(['mt-1 text-sm' => ! $embedded, 'pos-muted mt-1' => $embedded])>
                                {{ auth()->user()->device?->isOnline() ? 'Menunggu pembeli tap kartu...' : 'Menunggu console online...' }}
                            </p>
                        </div>

                    @elseif($status === 'success')
                        <div class="text-center py-8">
                            <div @class(['pos-state-icon pos-state-icon-success' => $embedded, 'mx-auto h-16 w-16 bg-green-100 rounded-full flex items-center justify-center' => ! $embedded])>
                                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <p @class(['mt-3' => ! $embedded, 'pos-state-success mt-3' => $embedded])>{{ $message }}</p>
                            @if($cardInfo)
                                <div class="pos-card-info">
                                    <div>Pembeli: <span class="font-medium">{{ $cardInfo['user_name'] }}</span></div>
                                    <div>UID: <span class="font-mono">{{ $cardInfo['uid'] }}</span></div>
                                </div>
                            @endif
                            <div @class(['pos-summary text-sm' => $embedded, 'mt-4 space-y-1 text-sm' => ! $embedded])>
                                <div @class(['pos-summary-row' => $embedded, 'flex justify-between' => ! $embedded])>
                                    <span class="text-gray-600">Saldo Sebelum</span>
                                    <span class="font-medium">Rp {{ number_format($balanceBefore, 0, ',', '.') }}</span>
                                </div>
                                <div @class(['pos-summary-row' => $embedded, 'flex justify-between' => ! $embedded])>
                                    <span class="text-gray-600">Total</span>
                                    <span @class(['font-medium' => ! $embedded, 'pos-danger-text font-medium' => $embedded])>- Rp {{ number_format($total, 0, ',', '.') }}</span>
                                </div>
                                <div @class(['pos-summary-row pos-summary-total' => $embedded, 'flex justify-between border-t pt-2 font-semibold' => ! $embedded])>
                                    <span>Saldo Sekarang</span>
                                    <span @class(['pos-success-text' => $embedded, 'text-green-600' => ! $embedded])>Rp {{ number_format($balanceAfter, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>

                    @elseif($status === 'failed')
                        <div class="text-center py-8">
                            <div @class(['pos-state-icon pos-state-icon-failed' => $embedded, 'mx-auto h-16 w-16 bg-red-100 rounded-full flex items-center justify-center' => ! $embedded])>
                                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </div>
                            <p @class(['mt-3' => ! $embedded, 'pos-state-failed mt-3' => $embedded])>{{ $message }}</p>
                            <button
                                wire:click="$set('status', 'idle'), $set('message', ''), $set('transactionId', null)"
                                @class(['reset-transaction pos-btn-secondary' => $embedded, 'reset-transaction mt-4 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition' => ! $embedded])
                            >
                                Transaksi Baru
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@script
<script>
    (() => {
        // Polling lives inside the POS component so it always talks to this
        // component instance, even when several Livewire components share the
        // Filament page. The old app.js lookup used the first [wire:id] on the
        // page, which pointed at the panel shell instead of the POS.
        const polling = {
            interval: null,

            start(transactionId) {
                this.stop()

                if (!transactionId) {
                    return
                }

                this.interval = setInterval(async () => {
                    try {
                        const response = await fetch(`/api/transactions/${transactionId}/status`, {
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                        })

                        if (!response.ok) {
                            return
                        }

                        const data = await response.json()

                        if (data.success && data.transaction && data.transaction.status !== 'pending') {
                            this.stop()

                            await $wire.handlePollResult(data.transaction)
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

        window.kasirPolling = polling

        document.addEventListener('livewire:navigating', () => polling.stop())

        if (!window.kasirAutoResetBound) {
            window.kasirAutoResetBound = true

            // Emitted by KasirPos after a successful transaction, so the cashier
            // gets a clean screen for the next buyer.
            window.addEventListener('auto-reset', () => {
                setTimeout(() => window.location.reload(), 3000)
            })
        }
    })()
</script>
@endscript
