# 01: ProductResource scoped to kasir

**What to build:** Kasir only sees/manages their own products; admin sees all.

**Blocked by:** auth/02-filament-auth-access

**Status:** ready-for-agent

- [ ] `ProductResource::getEloquentQuery()` scopes to `kasir_id = auth()->id()` for kasir role
- [ ] Admin (role=admin) sees all products (no scope)
- [ ] Navigation group "Kasir", icon shopping-bag
- [ ] Policy `ProductPolicy` registered: `viewAny`, `view`, `create`, `update`, `delete` respect ownership
- [ ] Kasir cannot access other kasir's products via URL manipulation