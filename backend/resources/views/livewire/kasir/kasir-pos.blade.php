<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
            <h1 class="text-xl font-bold text-gray-900">Kasir Cashless</h1>
            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-600">{{ auth()->user()->name }}</span>
                @php($device = auth()->user()->device)
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

    <div class="max-w-4xl mx-auto px-4 py-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Product List -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow p-4">
                    <h2 class="text-lg font-semibold mb-4">Menu</h2>
                    
                    @if(empty($products))
                        <p class="text-gray-500 text-center py-8">Belum ada produk. Tambahkan di panel admin.</p>
                    @else
                        <div class="product-grid grid grid-cols-2 sm:grid-cols-3 gap-3">
                            @foreach($products as $product)
                                <button 
                                    wire:click="addToCart({{ $product['id'] }})"
                                    class="product-card p-4 border rounded-lg hover:bg-gray-50 transition text-left group"
                                    {{ $product['stock'] <= 0 ? 'disabled' : '' }}
                                >
                                    <div class="font-medium text-gray-900">{{ $product['name'] }}</div>
                                    <div class="text-sm text-gray-500">{{ $product['category'] ?? '' }}</div>
                                    <div class="text-lg font-bold text-green-600 mt-1">Rp {{ number_format($product['price'], 0, ',', '.') }}</div>
                                    <div class="text-xs text-gray-400 mt-1">Stok: {{ $product['stock'] }}</div>
                                    @if($product['stock'] <= 0)
                                        <div class="text-xs text-red-500 mt-1">Habis</div>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Cart & Transaction -->
            <div class="lg:col-span-1">
                <!-- Cart -->
                <div class="bg-white rounded-lg shadow p-4 mb-4">
                    <h2 class="text-lg font-semibold mb-4 flex justify-between items-center">
                        Keranjang
                        @if(!empty($cart))
                            <span class="text-sm text-gray-500">{{ count($cart) }} item</span>
                            <button wire:click="clearCart" class="ml-auto text-xs text-red-500 hover:text-red-700">Kosongkan</button>
                        @endif
                    </h2>

                    @if(empty($cart))
                        <p class="text-gray-500 text-center py-8">Keranjang kosong</p>
                    @else
                        <div class="space-y-3 max-h-64 overflow-y-auto">
                            @foreach($cart as $index => $item)
                                <div class="cart-item flex items-center justify-between p-2 bg-gray-50 rounded">
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium text-sm truncate">{{ $item['name'] }}</div>
                                        <div class="text-xs text-gray-500">Rp {{ number_format($item['price'], 0, ',', '.') }} x {{ $item['qty'] }}</div>
                                    </div>
                                    <div class="flex items-center gap-2 ml-2">
                                        <button wire:click="updateQty({{ $index }}, {{ $item['qty'] - 1 }})" class="cart-qty-dec w-6 h-6 rounded border text-xs hover:bg-gray-100">-</button>
                                        <span class="qty w-8 text-center text-sm">{{ $item['qty'] }}</span>
                                        <button wire:click="updateQty({{ $index }}, {{ $item['qty'] + 1 }})" class="cart-qty-inc w-6 h-6 rounded border text-xs hover:bg-gray-100">+</button>
                                        <button wire:click="removeFromCart({{ $index }})" class="cart-remove ml-2 text-red-500 hover:text-red-700 text-xs">✕</button>
                                    </div>
                                    <div class="text-right ml-2">
                                        <div class="font-semibold text-sm">Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <div class="border-t mt-4 pt-4">
                            <div class="flex justify-between text-lg font-bold">
                                <span>Total</span>
                                <span class="cart-total">Rp {{ number_format($total, 0, ',', '.') }}</span>
                            </div>
                            <button 
                                wire:click="checkout"
                                class="w-full mt-4 bg-green-600 text-white py-3 rounded-lg font-semibold hover:bg-green-700 transition disabled:opacity-50"
                                {{ $status !== 'idle' ? 'disabled' : '' }}
                            >
                                {{ $status === 'idle' ? 'Bayar (RFID)' : 'Memproses...' }}
                            </button>
                            @if($status === 'pending' || $status === 'processing')
                                <button 
                                    wire:click="cancelTransaction"
                                    class="w-full mt-2 bg-gray-200 text-gray-700 py-2 rounded-lg font-medium hover:bg-gray-300 transition"
                                >
                                    Batalkan
                                </button>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Transaction Status -->
                <div class="bg-white rounded-lg shadow p-4">
                    <h2 class="text-lg font-semibold mb-4">Status Transaksi</h2>
                    
                    @if($status === 'idle')
                        <div class="text-center py-8 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                            </svg>
                            <p class="mt-2">Siap transaksi baru</p>
                        </div>
                    
                    @elseif($status === 'pending')
                        <div class="text-center py-8">
                            <div class="animate-spin rounded-full h-12 w-12 border-4 border-green-500 border-t-transparent mx-auto"></div>
                            <p class="mt-3 text-gray-600">{{ $message }}</p>
                            <p class="text-sm text-gray-400 mt-1">Menunggu pembeli tap kartu...</p>
                        </div>
                    
                    @elseif($status === 'processing')
                        <div class="text-center py-8">
                            <div class="animate-spin rounded-full h-12 w-12 border-4 border-blue-500 border-t-transparent mx-auto"></div>
                            <p class="mt-3 text-gray-600">Memproses pembayaran...</p>
                            <p class="text-sm text-gray-400 mt-1">
                                {{ auth()->user()->device?->isOnline() ? 'Menunggu pembeli tap kartu...' : 'Menunggu console online...' }}
                            </p>
                        </div>
                    
                    @elseif($status === 'success')
                        <div class="text-center py-8">
                            <div class="mx-auto h-16 w-16 bg-green-100 rounded-full flex items-center justify-center">
                                <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <p class="mt-3 text-green-700 font-semibold">{{ $message }}</p>
                            @if($cardInfo)
                                <div class="mt-3 p-3 bg-green-50 rounded-lg text-left text-sm">
                                    <div>Pembeli: <span class="font-medium">{{ $cardInfo['user_name'] }}</span></div>
                                    <div>UID: <span class="font-mono">{{ $cardInfo['uid'] }}</span></div>
                                </div>
                            @endif
                            <div class="mt-4 space-y-1 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Saldo Sebelum</span>
                                    <span class="font-medium">Rp {{ number_format($balanceBefore, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Total</span>
                                    <span class="font-medium text-green-600">- Rp {{ number_format($total, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between border-t pt-2 font-semibold">
                                    <span>Saldo Sekarang</span>
                                    <span class="text-green-600">Rp {{ number_format($balanceAfter, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                    
                    @elseif($status === 'failed')
                        <div class="text-center py-8">
                            <div class="mx-auto h-16 w-16 bg-red-100 rounded-full flex items-center justify-center">
                                <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </div>
                            <p class="mt-3 text-red-700 font-semibold">{{ $message }}</p>
                            <button 
                                wire:click="$set('status', 'idle'), $set('message', ''), $set('transactionId', null)"
                                class="reset-transaction mt-4 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition"
                            >
                                Transaksi Baru
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Status polling lives in resources/js/app.js and is started from PHP with
         $this->js(), because inline scripts injected by a Livewire morph are
         not guaranteed to execute. --}}
</div>