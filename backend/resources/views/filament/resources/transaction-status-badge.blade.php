@php
    $colors = [
        'pending' => ['#b45309', 'rgba(245, 158, 11, 0.1)', 'Pending'],
        'success' => ['#047857', 'rgba(16, 185, 129, 0.1)', 'Success'],
        'failed' => ['#b91c1c', 'rgba(239, 68, 68, 0.1)', 'Failed'],
        'cancelled' => ['#374151', 'rgba(107, 114, 128, 0.1)', 'Cancelled'],
    ];

    [$foreground, $background, $label] = $colors[$status] ?? ['#374151', 'rgba(107, 114, 128, 0.1)', ucfirst((string) $status)];
@endphp

<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" style="color: {{ $foreground }}; background-color: {{ $background }};">{{ $label }}</span>