---
name: Kasir Cashless
description: School canteen RFID point-of-sale, built like a schoolyard sticker wall.
colors:
  paper: "#FFF8E7"
  surface: "#FFFDF7"
  surface-highlight: "#FFF1C2"
  ink: "#0B0B0B"
  ink-muted: "#5B5346"
  signal-red: "#FF4B3E"
  signal-red-ink: "#A51C12"
  signal-blue: "#2D5BFF"
  signal-yellow: "#FFC93C"
  signal-green: "#22C55E"
  signal-green-ink: "#0B6B32"
typography:
  display:
    fontFamily: "Archivo Black, Arial Black, sans-serif"
    fontSize: "clamp(2rem, 4.5vw, 3.25rem)"
    fontWeight: 400
    lineHeight: 0.95
    letterSpacing: "-0.03em"
  title:
    fontFamily: "Archivo Black, Arial Black, sans-serif"
    fontSize: "1.15rem"
    fontWeight: 400
    lineHeight: 1
    letterSpacing: "-0.02em"
  body:
    fontFamily: "Space Grotesk, Segoe UI, sans-serif"
    fontSize: "0.95rem"
    fontWeight: 400
    lineHeight: 1.45
    letterSpacing: "0em"
  number:
    fontFamily: "Space Mono, Consolas, monospace"
    fontSize: "1.05rem"
    fontWeight: 700
    lineHeight: 1.2
    letterSpacing: "-0.03em"
  label:
    fontFamily: "Space Mono, Consolas, monospace"
    fontSize: "0.7rem"
    fontWeight: 700
    lineHeight: 1
    letterSpacing: "0.12em"
rounded:
  sticker: "3px"
  panel: "4px"
spacing:
  xs: "0.45rem"
  sm: "0.75rem"
  md: "1.25rem"
  lg: "2.5rem"
components:
  button-primary:
    backgroundColor: "{colors.signal-blue}"
    textColor: "{colors.surface}"
    rounded: "{rounded.panel}"
    padding: "0.85rem 1rem"
    height: "3.25rem"
  button-destructive:
    backgroundColor: "{colors.signal-red}"
    textColor: "{colors.ink}"
    rounded: "{rounded.sticker}"
    height: "2.75rem"
    width: "2.75rem"
  panel:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.panel}"
    padding: "{spacing.md}"
  product-card:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.panel}"
    padding: "0.9rem"
---

# Design System: Kasir Cashless

## Overview

**Creative North Star: "Schoolyard Sticker"**

The canteen counter as a wall of stickers and rubber stamps: paper ground, thick ink outlines, hard offset shadows, bright red/blue/yellow signals. It should feel handmade and loud enough to read across a queue, but the loudness stops at the transaction status. The POS is the first shipped surface; the rest of the Filament panel still uses its default theme and is being rolled out phase by phase.

The system borrows neobrutalism's directness, not its chaos. Every panel is a card with a visible edge. Every state is a stamped label. Nothing floats without a shadow, and no shadow is blurred.

## Colors

- **Paper** `#FFF8E7` — page ground, also the surface behind the dot grid.
- **Surface** `#FFFDF7` — panels, cards, inputs.
- **Surface Highlight** `#FFF1C2` — product hover, receipt details, soft yellow blocks.
- **Ink** `#0B0B0B` — borders, body text, hard shadows.
- **Ink Muted** `#5B5346` — secondary labels, units, hints.
- **Signal Red** `#FF4B3E` — offline, destructive actions, failure.
- **Signal Blue** `#2D5BFF` — primary action and active navigation.
- **Signal Yellow** `#FFC93C` — pending, counters, sticker accents.
- **Signal Green** `#22C55E` — online, success, "Lunas".
- **Signal Red Ink** `#A51C12` and **Signal Green Ink** `#0B6B32` — text on paper/surface where the bright signal colors would fail contrast.

Dark mode keeps the same roles on a warm near-black ground: paper `#141210`, surface `#1F1A16`, ink/cream `#FFF6E4`, muted `#C9BFAE`. Signal colors lift to `#FF6B5E`, `#8FA6FF`, `#FFD75E`, `#4ADE80`. Hard shadows switch to cream so the edge stays visible.

**Named rule — color is never the only signal.** Status chips and stamps always pair color with a word ("Online", "Offline", "Menunggu", "Lunas", "Gagal") and an icon where one fits.

## Typography

- **Display — Archivo Black.** Page title, panel titles, stamp labels, primary buttons. Tight tracking, line-height at or below 1. Never used for paragraphs.
- **UI — Space Grotesk.** Body copy, product names, navigation, hints.
- **Numerals — Space Mono.** Prices, totals, quantities, card UIDs. Always tabular-feeling; keep the mono voice distinct from prose.

Hierarchy is carried by face and weight, not by many sizes. There are three sizes that matter on the POS: display, panel title, body. Labels are small mono uppercase with wide tracking.

**Named rule — numbers wear the mono uniform.** Any amount, count, or hardware ID uses Space Mono. A price in Space Grotesk is a bug.

## Layout

- POS desktop: two columns, menu `minmax(0, 1.6fr)` and a side rail `minmax(21rem, 1fr)`. The menu is the wide work surface; the cart and status rail stay narrow enough to scan without turning the head.
- POS mobile: one column; menu first, cart and status below. Product grid is two columns at phone width, three at `640px`, four at `1280px`.
- Page content is capped at `92rem` inside Filament. Gutter comes from Filament (`px-4 / md:px-6 / lg:px-8`), not from the POS.
- Panel rhythm: `1.25rem` between panels, `0.85rem` between product cards, `1.25rem` panel padding. On phones, panel padding drops to `1rem`.
- The cart list scrolls after `18rem`; the status panel reserves `13.5rem` so the layout does not jump between states.
- Touch targets are at least `44px`; quantity and remove buttons are `2.75rem` squares.

## Elevation & Depth

Depth is **structural, not ambient**: a hard offset shadow says "this is a physical card on the counter". No blur, no soft glow, no zero-offset halos.

- `--nb-shadow`: `6px 6px 0 ink` — panels, primary button, hovered product card.
- `--nb-shadow-sm`: `4px 4px 0 ink` — console bar, product cards, sticker badges.
- Pressing a tactile control translates it toward the shadow and collapses the shadow (`translate(3px, 3px)`, `1px 1px 0`).
- Dark mode reduces offsets by about 1px because cream shadows read heavier.

**Named rule — shadows are ink, not light.** A shadow never uses a translucent black; it is the same ink as the border (or cream in dark mode).

## Shapes

- Borders: `3px solid ink` on panels, cards, primary buttons, console bar. `2px` on small chips, quantity buttons, internal dividers.
- Radius: `4px` on panels and primary buttons, `3px` on stickers and small controls, `2px` on stamps. Corners are softened, never pill-shaped.
- Stickers and stamps are deliberately rotated `-3deg` to `2deg`. Rotation is reserved for badges, never for content blocks.
- Dividers inside panels are `2px dashed ink`; the top rule of a panel is `3px solid ink`. Dashed means "receipt/ledger", solid means "container edge".

## Components

### Buttons

- **Primary:** Signal Blue fill, cream text (dark ink text in dark mode's lighter blue), `3px` ink border, `6px` hard shadow, uppercase Archivo Black. Hover lifts `-2px` and grows the shadow; active presses into it.
- **Secondary:** surface fill, ink text, `4px` shadow. Used for "Batalkan"; hover turns Signal Red.
- **Reset:** green fill, dark text. Appears only after a failed or cancelled transaction.
- **Quantity:** `2.75rem` square, surface fill, `2px` border/shadow. Remove is Signal Red.
- All buttons use `:focus-visible` with a `3px` blue outline and `3px` offset; primary buttons use ink so the ring survives the blue fill.

### Chips

Small mono uppercase labels with `2px` ink border, `2px` shadow, rotated `-1deg` to `2deg`. Used for item counts ("8 item") and status stamps. Yellow = pending/count, blue = processing, green = success, red = failure, surface = idle.

### Cards / Containers

- **Panel:** surface fill, `3px` ink border, `4px` radius, `6px` shadow, `1.25rem` padding. Header has a `3px` bottom rule and a title + right-side chip.
- **Product card:** surface fill, `3px` border, `4px` shadow, min-height `8.5rem`. Category micro-label, name, price pinned to the bottom, stock below. Hover turns the card pale yellow and lifts it; disabled drops opacity to `0.55` and shrinks the shadow.
- **Cart line:** two-row grid: name/unit price and subtotal on top, quantity controls below. `2px` dashed divider, like a receipt.

### Inputs / Fields

No custom POS inputs yet. If one is added, it inherits the panel language: surface fill, `3px` ink border, `4px` radius, no soft focus glow, visible ink/blue focus ring.

### Navigation

- POS-scoped panel chrome only: `body:has(.kasir-pos-embedded)` themes the sidebar, topbar, and logo.
- Sidebar/topbar use paper, `3px` ink rules and no blur. Active nav item is Signal Blue with `2px` ink border and `3px` shadow; its label/icon flip to the on-blue color.
- The sidebar overlay is suppressed on the POS because the page closes the drawer on phones; the drawer can still be opened.

### Signature Component: Transaction Stamp

The status panel behaves like a rubber stamp card:

- **Idle:** neutral stamp "Siap"; ticket icon on a yellow sticker; copy "Pilih menu, lalu tekan Bayar."
- **Pending:** yellow "Menunggu" stamp, rotating square spinner, "Menunggu pembeli tap kartu..."
- **Processing:** blue "Proses" stamp, blue spinner, console-aware hint.
- **Success:** green "Lunas" stamp with a one-shot stamp-in animation; green check sticker; receipt block with buyer, UID, balance before, total, balance after. Auto-reset reloads only if the success panel is still on screen after 3 seconds.
- **Failure:** red "Gagal" stamp; red X sticker; plain-language reason; green "Transaksi Baru" button.

Stamps are the loudest element by design. The rest of the page stays readable around them.

## Do's and Don'ts

### Do

- Keep the paper ground and the dot grid; they make the ink edges legible.
- Use one hard shadow direction: down-right, always ink/cream.
- Rotate only stickers and stamps, never panels or text blocks.
- Pair every status color with a word and, where possible, an icon.
- Use Space Mono for every amount, count, and hardware ID.
- Keep the POS path one tap: product → cart → Bayar. No confirmation dialogs for the normal path.
- Test both themes; dark mode is a first-class palette, not an inversion filter.

### Don't

- Don't add gradients, glassmorphism, soft drop shadows, or zero-offset halos.
- Don't use Signal Red or Signal Green as body text on paper; use the ink variants.
- Don't put Archivo Black in paragraphs, table cells, or long labels.
- Don't add a second accent family (purple, teal, pink) without retiring one of the primaries.
- Don't round cards past `4px` or turn buttons into pills.
- Don't hide the console state. Offline is a normal state with a visible explanation.
- Don't animate decorative elements continuously; only the pending/processing spinner loops, and it stops under `prefers-reduced-motion`.
