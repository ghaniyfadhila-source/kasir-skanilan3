# ADR-0001: Supabase → VPS Migration Strategy

**Status**: Accepted
**Date**: 2026-10-12
**Deciders**: Ghaniy Fadhil Altaf

## Context

Project `kasir-skanilan3` starts on **Supabase** (free tier) for rapid MVP development. Target production is a **self-hosted VPS** (Ubuntu + Docker) for full control, cost predictability, and data sovereignty.

Supabase provides:
- PostgreSQL database
- Auth (email/password, OAuth)
- Realtime (WebSocket)
- Storage (file uploads)
- Edge Functions (Deno)

VPS will replace these with:
- PostgreSQL (same engine, `pg_dump`/`pg_restore` compatible)
- Laravel Sanctum + custom JWT for API/device auth
- Laravel Reverb (WebSocket server, API-compatible with Pusher protocol)
- S3-compatible storage (MinIO on VPS or Cloudflare R2)
- Laravel Jobs/Queues for background work

## Decision

**Adopt a "lift-and-shift compatible" architecture from day one:**

1. **Database**: Use plain PostgreSQL via `DATABASE_URL`. No Supabase-specific extensions (pgcrypto, uuid-ossp OK; avoid `supabase_*` schemas, RLS policies, `pg_graphql`, `pg_net`).
2. **Auth**: 
   - Web (admin/kasir): Laravel Sanctum (SPA) + session cookies
   - Devices (ESP8266): Long-lived API tokens (`dev_*` prefix) stored in `devices.api_token`
   - **Do not use Supabase Auth** for any user/device.
3. **Realtime**: Laravel Reverb from start. Broadcast events via `ShouldBroadcast` on private channels `device.{id}` and `admin.transactions`. Client (web + ESP8266) uses standard Pusher protocol.
4. **Storage**: If file uploads needed (bukti bayar, import CSV), use Laravel Flysystem with `s3` driver pointed at MinIO (local) or R2 (cloud). **No Supabase Storage**.
5. **Edge Functions**: **Never use**. Move all server-side logic to Laravel Controllers, Jobs, Commands.
6. **Migrations**: Standard Laravel migrations. Run `php artisan migrate` on VPS after `pg_restore`.
7. **Secrets**: `.env` on VPS (Docker secrets / `.env.production`). No Supabase Vault.

## Migration Checklist (run when moving to VPS)

| Step | Action | Command / Note |
|------|--------|----------------|
| 1 | Dump Supabase DB | `pg_dump -h db.xxx.supabase.co -U postgres -d postgres -Fc > dump.dump` |
| 2 | Provision VPS | Ubuntu 22.04 + Docker + Docker Compose |
| 3 | Deploy stack | `docker compose up -d` (nginx, php-fpm, postgres, redis, reverb, minio) |
| 4 | Restore DB | `pg_restore -h localhost -U postgres -d kasir_skanilan dump.dump` |
| 5 | Run migrations | `php artisan migrate --force` |
| 6 | Update DNS | Point domain to VPS IP |
| 7 | Rotate secrets | New `APP_KEY`, `DB_PASSWORD`, `REVERB_*`, `MINIO_*`, device tokens |
| 8 | Verify devices | ESP8266 re-register automatically on first boot (token regenerated) |
| 9 | Smoke test | Admin login, kasir flow, device tap, WebSocket live feed |

## Consequences

**Positive:**
- Zero vendor lock-in
- Predictable monthly cost (VPS ~$5–10 vs Supabase Pro $25+)
- Full control over Postgres tuning, backups, extensions
- Reverb scales horizontally via Redis

**Negative:**
- Operational burden (backups, updates, monitoring, SSL)
- No managed auth UI (build own or use Filament)
- Reverb requires Redis for horizontal scaling (added infra)
- MinIO/R2 setup for storage

## Alternatives Considered

| Option | Verdict |
|--------|---------|
| Stay on Supabase forever | Rejected: cost at scale, cold starts, limited control |
| Firebase + Cloud Functions | Rejected: NoSQL mismatch, vendor lock-in worse |
| PlanetScale (MySQL) | Rejected: PostgreSQL features needed (JSONB, advisory locks) |
| Railway / Render / Fly.io | Acceptable alternative to raw VPS; same architecture applies |

## References

- [Laravel Reverb Docs](https://reverb.laravel.com)
- [Supabase PostgreSQL Compatibility](https://supabase.com/docs/guides/database/postgres)
- [MinIO S3 Gateway](https://min.io/docs/minio/linux/integrations/s3-gateway.html)