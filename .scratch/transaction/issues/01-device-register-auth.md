# 01: Device register & auth middleware

**What to build:** Device can register via API, get token, subsequent requests authenticated via Sanctum device guard.

**Blocked by:** device/01-device-resource-crud, database/02-model-relationships

**Status:** ready-for-agent

- [ ] Route `POST /api/device/register` accepts `device_id`, `firmware_version`
- [ ] Creates/finds device by `device_id`, generates `api_token` if new
- [ ] Returns device info including `api_token`
- [ ] Sanctum guard `device` configured in `config/auth.php` using `devices` provider
- [ ] Middleware `auth:sanctum` on device routes validates `Authorization: Bearer <api_token>`
- [ ] Device model uses `HasApiTokens` trait
- [ ] Test: POST register → get token → GET with token header → 200, without → 401