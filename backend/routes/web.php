<?php

use App\Filament\Pages\KasirPos;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    if (auth()->check()) {
        if (auth()->user()->hasRole('admin')) {
            return redirect()->route('filament.admin.pages.dashboard');
        }

        return redirect()->to(KasirPos::getUrl());
    }

    return redirect()->route('login');
});

// The POS now lives inside the Filament panel. Keep the old URL and route name
// so bookmarks and older links keep working; both point at the panel page.
Route::get('/kasir/pos', fn () => redirect()->to(KasirPos::getUrl(), 301))->name('kasir.pos');

require __DIR__.'/auth.php';
