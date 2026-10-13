# 02: Filament auth & panel access control

**What to build:** Admin panel login works, role-based access control redirects correctly.

**Blocked by:** auth/01-spatie-roles-seeding

**Status:** ready-for-agent

- [ ] `php artisan filament:install --panels` completes
- [ ] Admin user (seeded) can login at `/admin`
- [ ] Kasir user (seeded) logs in → redirected to POS page (not admin panel)
- [ ] Guest accessing `/admin` → redirected to login
- [ ] Policies registered in `AuthServiceProvider` for `Device`, `Product`, `Transaction`
- [ ] Filament resources respect policies (admin sees all, kasir sees own)