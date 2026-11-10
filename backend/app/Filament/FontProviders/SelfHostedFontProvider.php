<?php

namespace App\Filament\FontProviders;

use Filament\FontProviders\Contracts\FontProvider;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class SelfHostedFontProvider implements FontProvider
{
    public function getHtml(string $family, ?string $url = null): Htmlable
    {
        // The face is declared with @font-face in the panel theme CSS, so there
        // is no CDN stylesheet to point at. Setting the panel font family still
        // feeds the --font-family variable used across the layout.
        return new HtmlString('');
    }
}
