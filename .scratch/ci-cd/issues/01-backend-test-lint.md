# 01: GitHub Actions: backend test + lint

**What to build:** CI workflow runs Pest tests with coverage, Pint lint on every push/PR.

**Blocked by:** setup/01-project-scaffolding

**Status:** ready-for-agent

- [ ] Workflow `.github/workflows/ci.yml` triggers on push/PR to main/develop
- [ ] Job `backend-test`:
  - Ubuntu latest, PHP 8.3
  - PostgreSQL 16 service (test database)
  - Composer cache
  - `composer install --prefer-dist`
  - `php artisan key:generate`, `php artisan migrate --force`
  - `php artisan test --coverage --min=50`
- [ ] Job `backend-lint`:
  - `php vendor/bin/pint --test`
- [ ] Both jobs must pass for PR merge
- [ ] Coverage report uploaded (optional)