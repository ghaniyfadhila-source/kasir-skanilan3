<?php

namespace App\Filament\Resources\RfidCardResource\Pages;

use App\Filament\Resources\RfidCardResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRfidCards extends ListRecords
{
    protected static string $resource = RfidCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
