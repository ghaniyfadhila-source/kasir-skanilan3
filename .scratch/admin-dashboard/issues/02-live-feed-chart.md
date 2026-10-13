# 02: Live transaction feed + chart

**What to build:** Real-time transaction feed widget and daily revenue chart.

**Blocked by:** transaction/04-websocket-channels-broadcasting, kasir-ui/03-timeout-cancel-handling

**Status:** ready-for-agent

- [ ] Widget `LiveTransactionFeed`: listens to `private-admin.transactions` channel
- [ ] On `transaction.completed`: prepend to feed list (max 50 items)
- [ ] Feed item: time, device name, pembeli (card user_name), total, status badge
- [ ] Widget `RevenueChart`: Filament Chart (line/bar) showing daily total revenue for last 30 days
- [ ] Data from `Transaction::where('status', 'success')->groupByDate('created_at')->sum('total')`
- [ ] Chart updates daily (or on new transaction via WS)
- [ ] Both widgets on admin dashboard (Filament home page)