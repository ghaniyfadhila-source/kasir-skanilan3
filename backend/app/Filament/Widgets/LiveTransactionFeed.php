<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class LiveTransactionFeed extends Widget
{
    protected static string $view = 'filament.widgets.live-transaction-feed';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    protected static ?string $heading = 'Live Transaksi';

    protected static ?string $description = 'Real-time transaction feed';

    public function getListeners(): array
    {
        return [
            'echo:private-admin.transactions,.transaction.completed' => 'onTransactionCompleted',
        ];
    }

    public function onTransactionCompleted(array $data): void
    {
        $this->dispatch('transaction-completed', $data);
    }
}
