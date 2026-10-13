# 02: Model relationships & accessors

**What to build:** All Eloquent models have complete relationships, casts, and helper methods.

**Blocked by:** database/01-run-migrations

**Status:** ready-for-agent

- [ ] `User` model: `device()` belongsTo, `products()` hasMany, `isAdmin()`, `isKasir()`, `HasRoles` trait
- [ ] `Device` model: `kasir()` belongsTo, `transactions()` hasMany, `generateApiToken()`, `isOnline()` (last_seen_at < 30s), `HasApiTokens` trait
- [ ] `Product` model: `kasir()` belongsTo, price cast to `decimal:2`, stock integer
- [ ] `RfidCard` model: `registeredBy()` belongsTo, `transactions()` hasMany, `hasSufficientBalance()`, `deductBalance()`, `addBalance()`, balance cast `decimal:2`
- [ ] `Transaction` model: `device()` belongsTo, `rfidCard()` belongsTo, `items` cast `array`, money casts `decimal:2`, `isPending()`, `isExpired()`, `markSuccess()`, `markFailed()`, `markCancelled()`
- [ ] All models use `HasFactory` trait