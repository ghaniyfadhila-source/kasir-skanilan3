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

    // Plain Widget has no heading support in Filament v3, so the view asks for it.
    public function getHeading(): ?string
    {
        return static::$heading;
    }

    public function getDescription(): ?string
    {
        return static::$description;
    }

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
