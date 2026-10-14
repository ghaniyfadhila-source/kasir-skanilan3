<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\DeviceResource;
use App\Models\Device;
use Filament\Widgets\Widget;

class DeviceStatusGrid extends Widget
{
    protected static string $view = 'filament.widgets.device-status-grid';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public function getDevices(): array
    {
        return Device::with('kasir')
            ->orderBy('name')
            ->get()
            ->map(fn ($device) => [
                'id' => $device->id,
                'name' => $device->name,
                'device_id' => $device->device_id,
                'status' => $device->status,
                'is_online' => $device->isOnline(),
                'last_seen_at' => $device->last_seen_at,
                'kasir_name' => $device->kasir?->name,
                'edit_url' => DeviceResource::getUrl('edit', ['record' => $device]),
            ])
            ->toArray();
    }
}
