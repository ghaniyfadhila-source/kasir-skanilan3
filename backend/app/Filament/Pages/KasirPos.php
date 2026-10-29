<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class KasirPos extends Page
{
    protected static ?string $slug = 'pos';

    protected static ?string $title = 'Kasir Cashless';

    protected static ?string $navigationLabel = 'POS';

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Kasir';

    protected static ?int $navigationSort = -10;

    protected static string $view = 'filament.pages.kasir-pos';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('kasir') ?? false;
    }
}
