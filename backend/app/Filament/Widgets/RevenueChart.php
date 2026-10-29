<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class RevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Pendapatan Harian (30 Hari Terakhir)';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = Transaction::query()
            ->where('status', 'success')
            ->where('created_at', '>=', now()->subDays(30))
            ->when(
                ! auth()->user()?->hasRole('admin'),
                fn ($query) => $query->whereHas('device', fn ($device) => $device->where('kasir_id', auth()->id()))
            )
            ->selectRaw('DATE(created_at) as date, SUM(total) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $dates = [];
        $totals = [];

        foreach ($data as $row) {
            $dates[] = Carbon::parse($row->date)->format('d/m');
            $totals[] = (float) $row->total;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total (Rp)',
                    'data' => $totals,
                    'backgroundColor' => 'rgba(245, 158, 11, 0.5)',
                    'borderColor' => 'rgb(245, 158, 11)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $dates,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) { return "Rp " + context.parsed.y.toLocaleString("id-ID"); }',
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return "Rp " + value.toLocaleString("id-ID"); }',
                    ],
                ],
            ],
        ];
    }
}
