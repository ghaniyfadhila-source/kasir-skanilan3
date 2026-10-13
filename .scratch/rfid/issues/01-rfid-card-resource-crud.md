# 01: RfidCardResource CRUD + UID input

**What to build:** Admin can create/edit RFID cards with manual UID input (hex format).

**Blocked by:** auth/02-filament-auth-access

**Status:** ready-for-agent

- [ ] `RfidCardResource`: only accessible by admin role (`canViewAny()` checks `hasRole('admin')`)
- [ ] Form fields: `uid` (required, unique, max 50, helper text: "Format hex, contoh: A1B2C3D4"), `user_name` (required), `balance` (numeric, prefix Rp, step 1000, min 0, default 0), `is_active` (toggle, default true), `registered_by` (select user, default current admin, disabled)
- [ ] Table columns: UID (monospace, copyable), user_name, balance (money IDR), is_active (icon), registered_by, created_at
- [ ] Filters: is_active ternary, registered_by select
- [ ] UID stored uppercase (mutator or form save)