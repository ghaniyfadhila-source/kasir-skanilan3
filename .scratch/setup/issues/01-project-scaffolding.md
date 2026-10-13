# 01: Project scaffolding & dependencies

**What to build:** Fresh Laravel 11 project with all required packages installed. `composer install` passes, all packages (sanctum, reverb, spatie, filament, livewire) are available.

**Blocked by:** None (can start immediately)

**Status:** ready-for-agent

- [ ] `composer create-project laravel/laravel backend` (or use existing scaffold)
- [ ] `composer require laravel/sanctum laravel/reverb spatie/laravel-permission filament/filament livewire/livewire`
- [ ] `composer install` passes without errors
- [ ] `php artisan --version` shows Laravel 11.x
- [ ] All service providers auto-discovered