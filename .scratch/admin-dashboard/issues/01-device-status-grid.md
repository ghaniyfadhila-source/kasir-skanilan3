# 01: Device status grid widget

**What to build:** Filament widget showing all devices with real-time online/offline status.

**Blocked by:** device/02-device-status-monitoring, transaction/04-websocket-channels-broadcasting

**Status:** ready-for-agent

- [ ] Widget class `DeviceStatusGrid` extends `Filament\Widgets\Widget`
- [ ] Grid of cards: device name, device_id, status badge, online indicator (green/red)
- [ ] Online = `last_seen_at` < 30s (computed via `Device::isOnline()`)
- [ ] Auto-refresh via WebSocket: listen to `private-admin.transactions` or device heartbeat
- [ ] Click card → link to DeviceResource edit page
- [ ] Responsive grid: 1 col mobile, 2 tablet, 4 desktop