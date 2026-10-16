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

    protected static ?string $heading = 'Device Status Grid';

    protected static ?string $description = 'Status online/offline tiap device kasir';

    // Plain Widget has no heading support in Filament v3, so the view asks for it.
    public function getHeading(): ?string
    {
        return static::$heading;
    }

    public function getDescription(): ?string
    {
        return static::$description;
    }

    /**
     * Livewire v3 computed property. Values must stay scalar/array so the
     * component snapshot stays serialisable (no Carbon instances).
     */
    public function getDevicesProperty(): array
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
                'last_seen_at' => $device->last_seen_at?->format('d/m/Y H:i'),
                'kasir_name' => $device->kasir?->name,
                'edit_url' => DeviceResource::getUrl('edit', ['record' => $device]),
            ])
            ->toArray();
    }
}
