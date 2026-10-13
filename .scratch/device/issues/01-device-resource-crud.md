# 01: DeviceResource CRUD + assign kasir

**What to build:** Admin can create/edit/delete devices, assign to kasir via dropdown.

**Blocked by:** auth/02-filament-auth-access

**Status:** ready-for-agent

- [ ] `DeviceResource` in Filament: list, create, edit, delete
- [ ] Form fields: `device_id` (unique, required), `name`, `status` (select: active/inactive/maintenance), `kasir_id` (select user with role=kasir)
- [ ] `api_token` field: disabled, shows current token, copyable
- [ ] `firmware_version`, `ip_address`, `last_seen_at`: disabled, read-only
- [ ] Validation: `device_id` unique, `kasir_id` must have role kasir
- [ ] On create: auto-generate `api_token` via `Device::generateApiToken()`