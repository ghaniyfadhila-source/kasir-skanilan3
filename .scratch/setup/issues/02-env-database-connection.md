# 02: Env & database connection

**What to build:** `.env` configured with Supabase `DATABASE_URL`, migrations can connect, Reverb keys generated.

**Blocked by:** 01-project-scaffolding

**Status:** ready-for-agent

- [ ] Copy `.env.example` to `.env`
- [ ] Set `DATABASE_URL=postgresql://postgres:password@db.xxx.supabase.co:5432/postgres`
- [ ] Run `php artisan key:generate`
- [ ] Run `php artisan reverb:install` (generates REVERB_APP_ID, KEY, SECRET)
- [ ] `php artisan migrate:status` connects to Supabase and shows "No migrations found"
- [ ] Verify `config/database.php` uses `env('DATABASE_URL')` for pgsql connection