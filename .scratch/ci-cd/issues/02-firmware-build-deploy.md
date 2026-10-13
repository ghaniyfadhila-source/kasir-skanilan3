# 02: Firmware build check + deploy staging

**What to build:** CI compiles firmware, optional deploy to staging server.

**Blocked by:** firmware/01-platformio-project, ci-cd/01-backend-test-lint

**Status:** ready-for-agent

- [ ] Job `firmware-build`:
  - Ubuntu latest
  - `pip install platformio`
  - `pio run -e nodemcuv3` (compile only, continue-on-error true)
- [ ] Job `deploy-staging` (manual trigger, needs `backend-test` success):
  - SSH to staging server
  - Deploy backend via Docker Compose / rsync
  - Run migrations
  - Restart Reverb
  - Health check endpoint
- [ ] Environment: `staging` with protection rules
- [ ] Secrets: `STAGING_HOST`, `STAGING_USER`, `STAGING_SSH_KEY`, `STAGING_DB_URL`