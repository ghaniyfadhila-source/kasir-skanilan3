@php($device = auth()->user()->device)

<div class="kasir-pos kasir-pos-embedded" data-kasir-pos>
    <div class="pos-console-bar">
        <span @class(['pos-console-dot', 'is-online' => $device?->isOnline()]) aria-hidden="true"></span>
        <span class="pos-console-label">Console</span>
        <span class="pos-console-name">{{ $device?->name ?? 'Console belum tertaut' }}</span>
        <span @class(['pos-console-state', 'is-online' => $device?->isOnline()])>
            {{ $device?->isOnline() ? 'Online' : 'Offline' }}
        </span>
        @if (! $device?->isOnline())
            <span class="pos-console-hint">Transaksi akan menunggu console online.</span>
        @endif
    </div>

    <div class="pos-grid">
        <section class="pos-panel pos-menu-panel" aria-labelledby="pos-menu-title">
            <header class="pos-panel-head">
                <h2 class="pos-panel-title" id="pos-menu-title">Menu</h2>
                <span class="pos-count">{{ count($products) }} item</span>
            </header>

            @if(empty($products))
                <p class="pos-empty">Belum ada produk. Tambahkan di panel admin.</p>
            @else
                <div class="product-grid pos-product-grid">
                    @foreach($products as $product)
                        <button
                            wire:click="addToCart({{ $product['id'] }})"
                            class="product-card pos-product-card"
                            {{ $product['stock'] <= 0 ? 'disabled' : '' }}
                        >
                            <span class="pos-product-cat">{{ $product['category'] ?? 'Menu' }}</span>
                            <span class="pos-product-name">{{ $product['name'] }}</span>
                            <span class="pos-price">Rp {{ number_format($product['price'], 0, ',', '.') }}</span>
                            <span class="pos-stock">Stok {{ $product['stock'] }}</span>
                            @if($product['stock'] <= 0)
                                <span class="pos-danger-text pos-product-out">Habis</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            @endif
        </section>

        <div class="pos-side">
            <section class="pos-panel pos-cart-panel" aria-labelledby="pos-cart-title">
                <header class="pos-panel-head">
                    <h2 class="pos-panel-title" id="pos-cart-title">Keranjang</h2>
                    @if(!empty($cart))
                        <div class="pos-cart-tools">
                            <span class="pos-count">{{ count($cart) }} item</span>
                            <button wire:click="clearCart" class="pos-clear">Kosongkan</button>
                        </div>
                    @endif
                </header>

                @if(empty($cart))
                    <p class="pos-empty">Keranjang kosong</p>
                @else
                    <div class="pos-cart-list">
                        @foreach($cart as $index => $item)
                            <div class="cart-item pos-cart-item">
                                <div class="pos-cart-info">
                                    <span class="pos-cart-name">{{ $item['name'] }}</span>
                                    <span class="pos-cart-unit">Rp {{ number_format($item['price'], 0, ',', '.') }} x {{ $item['qty'] }}</span>
                                </div>
                                <div class="pos-cart-subtotal">Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</div>
                                <div class="pos-cart-controls">
                                    <button wire:click="updateQty({{ $index }}, {{ $item['qty'] - 1 }})" class="cart-qty-dec pos-qty-btn" aria-label="Kurangi {{ $item['name'] }}">-</button>
                                    <span class="qty">{{ $item['qty'] }}</span>
                                    <button wire:click="updateQty({{ $index }}, {{ $item['qty'] + 1 }})" class="cart-qty-inc pos-qty-btn" aria-label="Tambah {{ $item['name'] }}">+</button>
                                    <button wire:click="removeFromCart({{ $index }})" class="cart-remove pos-remove-btn" aria-label="Hapus {{ $item['name'] }}">✕</button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="pos-total-row">
                        <span>Total</span>
                        <span class="cart-total">Rp {{ number_format($total, 0, ',', '.') }}</span>
                    </div>

                    <button
                        wire:click="checkout"
                        class="pos-btn-primary"
                        {{ $status !== 'idle' ? 'disabled' : '' }}
                    >
                        {{ $status === 'idle' ? 'Bayar (RFID)' : 'Memproses...' }}
                    </button>

                    @if($status === 'pending' || $status === 'processing')
                        <button wire:click="cancelTransaction" class="pos-btn-secondary pos-btn-cancel">
                            Batalkan
                        </button>
                    @endif
                @endif
            </section>

            <section @class(['pos-panel pos-status-card', 'pos-status-'.$status]) aria-labelledby="pos-status-title">
                <header class="pos-panel-head">
                    <h2 class="pos-panel-title" id="pos-status-title">Status Transaksi</h2>
                    <span class="pos-status-stamp">
                        @switch($status)
                            @case('pending') Menunggu @break
                            @case('processing') Proses @break
                            @case('success') Lunas @break
                            @case('failed') Gagal @break
                            @default Siap
                        @endswitch
                    </span>
                </header>

                <div class="pos-status-body" aria-live="polite">
                    @if($status === 'idle')
                        <div class="pos-empty">
                            <div class="pos-empty-icon" aria-hidden="true">
                                <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                    <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                                    <path d="M3 10h18"></path>
                                    <path d="M7 15h4"></path>
                                </svg>
                            </div>
                            <p class="pos-empty-title">Siap transaksi baru</p>
                            <p class="pos-empty-hint">Pilih menu, lalu tekan Bayar.</p>
                        </div>

                    @elseif($status === 'pending')
                        <div class="pos-state">
                            <div class="pos-spinner" aria-hidden="true"></div>
                            <p class="pos-state-title">{{ $message }}</p>
                            <p class="pos-state-hint">Menunggu pembeli tap kartu...</p>
                        </div>

                    @elseif($status === 'processing')
                        <div class="pos-state">
                            <div class="pos-spinner pos-spinner-blue" aria-hidden="true"></div>
                            <p class="pos-state-title">Memproses pembayaran...</p>
                            <p class="pos-state-hint">
                                {{ auth()->user()->device?->isOnline() ? 'Menunggu pembeli tap kartu...' : 'Menunggu console online...' }}
                            </p>
                        </div>

                    @elseif($status === 'success')
                        <div class="pos-state">
                            <div class="pos-state-icon pos-state-icon-success" aria-hidden="true">
                                <svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <p class="pos-state-title pos-state-success">{{ $message }}</p>
                            @if($cardInfo)
                                <div class="pos-card-info">
                                    <div>Pembeli: <strong>{{ $cardInfo['user_name'] }}</strong></div>
                                    <div>UID: <span class="pos-mono">{{ $cardInfo['uid'] }}</span></div>
                                </div>
                            @endif
                            <div class="pos-summary">
                                <div class="pos-summary-row">
                                    <span>Saldo Sebelum</span>
                                    <span>Rp {{ number_format($balanceBefore, 0, ',', '.') }}</span>
                                </div>
                                <div class="pos-summary-row">
                                    <span>Total</span>
                                    <span class="pos-danger-text">- Rp {{ number_format($total, 0, ',', '.') }}</span>
                                </div>
                                <div class="pos-summary-row pos-summary-total">
                                    <span>Saldo Sekarang</span>
                                    <span class="pos-success-text">Rp {{ number_format($balanceAfter, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>

                    @elseif($status === 'failed')
                        <div class="pos-state">
                            <div class="pos-state-icon pos-state-icon-failed" aria-hidden="true">
                                <svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </div>
                            <p class="pos-state-title pos-state-failed">{{ $message }}</p>
                            <button
                                wire:click="$set('status', 'idle'), $set('message', ''), $set('transactionId', null)"
                                class="reset-transaction pos-btn-secondary"
                            >
                                Transaksi Baru
                            </button>
                        </div>
                    @endif
                </div>
            </section>
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
            // gets a clean screen for the next buyer. Only reload if the success
            // state is still on screen: a reset or a new page must not be
            // interrupted by a stale timer.
            window.addEventListener('auto-reset', () => {
                setTimeout(() => {
                    if (document.querySelector('.pos-status-success')) {
                        window.location.reload()
                    }
                }, 3000)
            })
        }
    })()
</script>
@endscript
