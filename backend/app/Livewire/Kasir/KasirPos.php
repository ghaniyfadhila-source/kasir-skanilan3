<?php

namespace App\Livewire\Kasir;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.app')]
class KasirPos extends Component
{
    public $products = [];

    public $cart = [];

    public $total = 0;

    public $selectedProductId = null;

    public $qty = 1;

    public $transactionId = null;

    public $status = 'idle'; // idle, pending, processing, success, failed

    public $message = '';

    public $balanceBefore = 0;

    public $balanceAfter = 0;

    public $cardInfo = null;

    public function mount()
    {
        $this->loadProducts();
    }

    public function loadProducts()
    {
        $this->products = auth()->user()->products()
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    public function addToCart($productId)
    {
        $product = collect($this->products)->firstWhere('id', $productId);
        if (! $product) {
            return;
        }

        if (($product['stock'] ?? 0) <= 0) {
            $this->dispatch('notify', message: 'Stok habis', type: 'error');

            return;
        }

        $existing = collect($this->cart)->firstWhere('product_id', $productId);
        if ($existing) {
            if ($existing['qty'] >= $product['stock']) {
                $this->dispatch('notify', message: 'Stok tidak mencukupi', type: 'error');

                return;
            }
            $existing['qty']++;
            $existing['subtotal'] = $existing['qty'] * $existing['price'];
        } else {
            $this->cart[] = [
                'product_id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'qty' => 1,
                'subtotal' => $product['price'],
            ];
        }
        $this->recalculateTotal();
    }

    public function removeFromCart($index)
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);
        $this->recalculateTotal();
    }

    public function updateQty($index, $qty)
    {
        if (! isset($this->cart[$index])) {
            return;
        }
        $product = collect($this->products)->firstWhere('id', $this->cart[$index]['product_id']);
        if (! $product || $qty > $product['stock']) {
            $qty = $product['stock'] ?? 1;
        }
        $this->cart[$index]['qty'] = max(1, $qty);
        $this->cart[$index]['subtotal'] = $this->cart[$index]['qty'] * $this->cart[$index]['price'];
        $this->recalculateTotal();
    }

    private function recalculateTotal()
    {
        $this->total = collect($this->cart)->sum('subtotal');
    }

    public function checkout()
    {
        if (empty($this->cart)) {
            $this->dispatch('notify', message: 'Keranjang kosong', type: 'error');

            return;
        }

        $this->status = 'pending';
        $this->message = 'Menunggu pembeli tap kartu...';

        $items = array_map(fn ($i) => [
            'product_id' => $i['product_id'],
            'name' => $i['name'],
            'qty' => $i['qty'],
            'price' => $i['price'],
            'subtotal' => $i['subtotal'],
        ], $this->cart);

        $response = $this->callApi('/api/transactions', 'POST', [
            'device_id' => auth()->user()->device->device_id ?? '',
            'items' => $items,
        ]);

        if ($response['success']) {
            $this->transactionId = $response['transaction']['id'];
            $this->status = 'processing';
            $this->startPolling();
        } else {
            $this->status = 'failed';
            $this->message = $response['message'] ?? 'Gagal membuat transaksi';
        }
    }

    public function startPolling()
    {
        $this->dispatch('start-polling', transactionId: $this->transactionId);
    }

    #[On('poll-result')]
    public function handlePollResult($data)
    {
        if ($data['status'] === 'success') {
            $this->status = 'success';
            $this->message = 'Transaksi berhasil!';
            $this->balanceBefore = $data['balance_before'] ?? 0;
            $this->balanceAfter = $data['balance_after'] ?? 0;
            $this->cardInfo = $data['card'] ?? null;
            $this->cart = [];
            $this->total = 0;
            $this->dispatch('notify', message: 'Transaksi berhasil', type: 'success');

            // Auto reset after 3 seconds
            $this->dispatch('auto-reset');
        } elseif ($data['status'] === 'failed') {
            $this->status = 'failed';
            $this->message = $data['message'] ?? 'Transaksi gagal';
            $this->dispatch('notify', message: $this->message, type: 'error');
        } elseif ($data['status'] === 'cancelled') {
            $this->status = 'failed';
            $this->message = 'Transaksi dibatalkan (timeout)';
            $this->dispatch('notify', message: $this->message, type: 'warning');
        }
        // If still pending, continue polling via JS
    }

    public function cancelTransaction()
    {
        if (! $this->transactionId) {
            return;
        }

        $response = $this->callApi("/api/transactions/{$this->transactionId}/cancel", 'POST');
        if ($response['success']) {
            $this->resetTransaction();
            $this->dispatch('notify', message: 'Transaksi dibatalkan', type: 'info');
        }
    }

    private function resetTransaction()
    {
        $this->transactionId = null;
        $this->status = 'idle';
        $this->message = '';
        $this->balanceBefore = 0;
        $this->balanceAfter = 0;
        $this->cardInfo = null;
    }

    private function callApi(string $url, string $method = 'GET', array $data = []): array
    {
        $device = auth()->user()->device;
        $token = $device?->api_token ?? '';

        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->retry(3, 100)
                ->{$method}("{$this->baseUrl()}{$url}", $data);

            return $response->json() ?? ['success' => false, 'message' => 'Invalid response'];
        } catch (ConnectionException $e) {
            return ['success' => false, 'message' => 'Tidak bisa terhubung ke server'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Terjadi kesalahan: '.$e->getMessage()];
        }
    }

    private function baseUrl(): string
    {
        return config('app.url').'/api';
    }

    public function render()
    {
        return view('livewire.kasir.kasir-pos');
    }
}
