# 03: Reverb server & broadcasting config

**What to build:** Reverb WebSocket server runs, broadcasting channels load, events can be broadcast.

**Blocked by:** 02-env-database-connection

**Status:** ready-for-agent

- [ ] `php artisan reverb:start` runs without error (port 8080)
- [ ] `routes/channels.php` loads and defines `device.{deviceId}` and `admin.transactions` channels
- [ ] Test broadcast: `php artisan tinker` → `broadcast(new App\Events\TestEvent())` → received on client
- [ ] `config/broadcasting.php` uses `reverb` driver with correct env vars
- [ ] Vite config includes Reverb client setup (optional for now)