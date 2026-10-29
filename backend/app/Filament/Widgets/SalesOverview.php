<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $transactions = Transaction::query()
            ->whereDate('created_at', today())
            ->when(
                ! auth()->user()?->hasRole('admin'),
                fn ($query) => $query->whereHas('device', fn ($device) => $device->where('kasir_id', auth()->id()))
            );

        $success = (clone $transactions)->where('status', 'success');

        return [
            Stat::make('Omzet Hari Ini', 'Rp '.number_format((float) (clone $success)->sum('total'), 0, ',', '.'))
                ->description('Transaksi sukses hari ini')
                ->color('success'),
            Stat::make('Transaksi Sukses', (clone $success)->count())
                ->description('Pembayaran selesai')
                ->color('primary'),
            Stat::make('Menunggu Tap', (clone $transactions)->where('status', 'pending')->count())
                ->description('Transaksi masih pending')
                ->color('warning'),
        ];
    }
}
