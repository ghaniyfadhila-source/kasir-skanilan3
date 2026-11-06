<x-filament-panels::page>
    <style>
        @font-face {
            font-family: 'Archivo Black';
            font-style: normal;
            font-weight: 400;
            font-display: swap;
            src: url('{{ asset('fonts/archivo-black-latin.woff2') }}') format('woff2');
        }
        @font-face {
            font-family: 'Space Grotesk';
            font-style: normal;
            font-weight: 400 700;
            font-display: swap;
            src: url('{{ asset('fonts/space-grotesk-latin.woff2') }}') format('woff2');
        }
        @font-face {
            font-family: 'Space Mono';
            font-style: normal;
            font-weight: 400;
            font-display: swap;
            src: url('{{ asset('fonts/space-mono-400-latin.woff2') }}') format('woff2');
        }
        @font-face {
            font-family: 'Space Mono';
            font-style: normal;
            font-weight: 700;
            font-display: swap;
            src: url('{{ asset('fonts/space-mono-700-latin.woff2') }}') format('woff2');
        }

        body:has(.kasir-pos-embedded) {
            --nb-paper: #fff8e7;
            --nb-surface: #fffdf7;
            --nb-surface-2: #fff1c2;
            --nb-ink: #0b0b0b;
            --nb-muted: #5b5346;
            --nb-red: #ff4b3e;
            --nb-red-ink: #a51c12;
            --nb-blue: #2d5bff;
            --nb-on-blue: #fffdf7;
            --nb-yellow: #ffc93c;
            --nb-green: #22c55e;
            --nb-green-ink: #0b6b32;
            --nb-dot: rgba(11, 11, 11, 0.1);
            --nb-shadow: 6px 6px 0 var(--nb-ink);
            --nb-shadow-sm: 4px 4px 0 var(--nb-ink);
            --nb-border: 3px solid var(--nb-ink);
            --nb-radius: 4px;
            --nb-font-display: 'Archivo Black', 'Arial Black', sans-serif;
            --nb-font-ui: 'Space Grotesk', 'Segoe UI', sans-serif;
            --nb-font-mono: 'Space Mono', 'Consolas', monospace;
            background-color: var(--nb-paper);
            background-image: radial-gradient(var(--nb-dot) 1px, transparent 1px);
            background-size: 18px 18px;
            color: var(--nb-ink);
            font-family: var(--nb-font-ui);
        }

        .dark body:has(.kasir-pos-embedded) {
            --nb-paper: #141210;
            --nb-surface: #1f1a16;
            --nb-surface-2: #3a3016;
            --nb-ink: #fff6e4;
            --nb-muted: #c9bfae;
            --nb-red: #ff6b5e;
            --nb-red-ink: #fca5a5;
            --nb-blue: #8fa6ff;
            --nb-on-blue: #141210;
            --nb-yellow: #ffd75e;
            --nb-green: #4ade80;
            --nb-green-ink: #86efac;
            --nb-dot: rgba(255, 246, 228, 0.08);
            --nb-shadow: 5px 5px 0 var(--nb-ink);
            --nb-shadow-sm: 3px 3px 0 var(--nb-ink);
        }

        body:has(.kasir-pos-embedded) .fi-sidebar,
        body:has(.kasir-pos-embedded) .fi-sidebar-header,
        body:has(.kasir-pos-embedded) .fi-topbar nav {
            background: var(--nb-paper);
        }

        body:has(.kasir-pos-embedded) .fi-sidebar {
            border-right: 3px solid var(--nb-ink);
        }

        /* The POS closes the sidebar on phones; a stuck Filament overlay would
           otherwise dim the whole counter. The drawer still opens on demand. */
        body:has(.kasir-pos-embedded) .fi-sidebar-close-overlay {
            display: none !important;
        }

        body:has(.kasir-pos-embedded) .fi-sidebar-header {
            border-bottom: 3px solid var(--nb-ink);
            box-shadow: none;
        }

        body:has(.kasir-pos-embedded) .fi-topbar nav {
            border-bottom: 3px solid var(--nb-ink);
            box-shadow: none;
        }

        body:has(.kasir-pos-embedded) .fi-logo {
            color: var(--nb-ink);
            font-family: var(--nb-font-display);
            letter-spacing: -0.02em;
        }

        body:has(.kasir-pos-embedded) .fi-sidebar-item-button {
            border-radius: 3px;
        }

        body:has(.kasir-pos-embedded) .fi-sidebar-item-label,
        body:has(.kasir-pos-embedded) .fi-sidebar-item-icon {
            color: var(--nb-ink);
        }

        body:has(.kasir-pos-embedded) .fi-sidebar-group-label {
            color: var(--nb-muted);
        }

        body:has(.kasir-pos-embedded) .fi-sidebar-item-button:hover {
            background: var(--nb-surface-2);
        }

        body:has(.kasir-pos-embedded) .fi-sidebar-item-active .fi-sidebar-item-button {
            border: 2px solid var(--nb-ink);
            background: var(--nb-blue);
            box-shadow: 3px 3px 0 var(--nb-ink);
            color: var(--nb-on-blue) !important;
        }

        body:has(.kasir-pos-embedded) .fi-sidebar-item-active .fi-sidebar-item-label,
        body:has(.kasir-pos-embedded) .fi-sidebar-item-active .fi-sidebar-item-icon {
            color: var(--nb-on-blue) !important;
        }

        body:has(.kasir-pos-embedded) .fi-header {
            align-items: flex-end;
            margin-bottom: 0.25rem;
        }

        body:has(.kasir-pos-embedded) .fi-header-heading {
            color: var(--nb-ink);
            font-family: var(--nb-font-display);
            font-size: clamp(2rem, 4.5vw, 3.25rem);
            font-weight: 400;
            letter-spacing: -0.03em;
            line-height: 0.95;
        }

        body:has(.kasir-pos-embedded) .fi-header-heading::before {
            content: 'Skanilan / Kantin';
            display: block;
            margin-bottom: 0.45rem;
            color: var(--nb-muted);
            font-family: var(--nb-font-mono);
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        body:has(.kasir-pos-embedded) .fi-header::after {
            content: 'POS';
            flex: none;
            align-self: center;
            padding: 0.35rem 0.75rem;
            border: var(--nb-border);
            border-radius: 3px;
            background: var(--nb-yellow);
            color: #0b0b0b;
            box-shadow: var(--nb-shadow-sm);
            font-family: var(--nb-font-display);
            font-size: 0.9rem;
            letter-spacing: 0.02em;
            transform: rotate(-3deg);
        }

        body:has(.kasir-pos-embedded) .fi-header-subheading {
            max-width: 46ch;
            margin-top: 0.45rem;
            color: var(--nb-muted);
            font-size: 0.95rem;
        }

        body:has(.kasir-pos-embedded) .fi-main {
            max-width: 92rem;
        }

        body:has(.kasir-pos-embedded) .fi-page > section {
            gap: 1.25rem;
            padding-top: 1.5rem;
            padding-bottom: 2.5rem;
        }

        .kasir-pos-embedded {
            color: var(--nb-ink);
            font-family: var(--nb-font-ui);
        }

        .kasir-pos-embedded *,
        .kasir-pos-embedded *::before,
        .kasir-pos-embedded *::after {
            box-sizing: border-box;
        }

        .kasir-pos-embedded :where(button, a):focus-visible {
            outline: 3px solid var(--nb-blue);
            outline-offset: 3px;
        }

        .pos-console-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.45rem 0.65rem;
            margin-bottom: 1.25rem;
            padding: 0.7rem 0.9rem;
            border: var(--nb-border);
            border-radius: var(--nb-radius);
            background: var(--nb-surface);
            box-shadow: var(--nb-shadow-sm);
        }

        .pos-console-dot {
            width: 0.8rem;
            height: 0.8rem;
            border: 2px solid var(--nb-ink);
            border-radius: 50%;
            background: var(--nb-red);
            box-shadow: 2px 2px 0 var(--nb-ink);
        }

        .pos-console-dot.is-online {
            background: var(--nb-green);
        }

        .pos-console-label {
            color: var(--nb-muted);
            font-family: var(--nb-font-mono);
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .pos-console-name {
            font-weight: 700;
        }

        .pos-console-state {
            margin-left: auto;
            padding: 0.15rem 0.5rem;
            border: 2px solid var(--nb-ink);
            border-radius: 3px;
            background: var(--nb-red);
            color: #0b0b0b;
            box-shadow: 2px 2px 0 var(--nb-ink);
            font-family: var(--nb-font-mono);
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .pos-console-state.is-online {
            background: var(--nb-green);
        }

        .pos-console-hint {
            flex-basis: 100%;
            color: var(--nb-red-ink);
            font-size: 0.85rem;
            font-weight: 600;
        }

        .pos-grid {
            display: grid;
            gap: 1.25rem;
            align-items: start;
        }

        @media (min-width: 1024px) {
            .pos-grid {
                grid-template-columns: minmax(0, 1.6fr) minmax(21rem, 1fr);
            }
        }

        .pos-panel {
            padding: 1.25rem;
            border: var(--nb-border);
            border-radius: var(--nb-radius);
            background: var(--nb-surface);
            box-shadow: var(--nb-shadow);
        }

        .pos-side > * + * {
            margin-top: 1.25rem;
        }

        .pos-panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 3px solid var(--nb-ink);
        }

        .pos-panel-title {
            margin: 0;
            font-family: var(--nb-font-display);
            font-size: 1.15rem;
            font-weight: 400;
            letter-spacing: -0.02em;
            line-height: 1;
            text-transform: uppercase;
        }

        .pos-count {
            flex: none;
            padding: 0.15rem 0.45rem;
            border: 2px solid var(--nb-ink);
            border-radius: 2px;
            background: var(--nb-yellow);
            color: #0b0b0b;
            box-shadow: 2px 2px 0 var(--nb-ink);
            font-family: var(--nb-font-mono);
            font-size: 0.7rem;
            font-weight: 700;
            transform: rotate(-1deg);
        }

        .pos-product-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.85rem;
        }

        @media (min-width: 640px) {
            .pos-product-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (min-width: 1280px) {
            .pos-product-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        .pos-product-card {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 0.2rem;
            min-height: 8.5rem;
            padding: 0.9rem;
            border: var(--nb-border);
            border-radius: var(--nb-radius);
            background: var(--nb-surface);
            color: var(--nb-ink);
            box-shadow: var(--nb-shadow-sm);
            cursor: pointer;
            text-align: left;
            transition: transform 0.12s ease, box-shadow 0.12s ease, background-color 0.12s ease;
        }

        .pos-product-card:hover:not(:disabled) {
            background: var(--nb-surface-2);
            box-shadow: var(--nb-shadow);
            transform: translate(-2px, -2px);
        }

        .pos-product-card:active:not(:disabled) {
            box-shadow: 1px 1px 0 var(--nb-ink);
            transform: translate(3px, 3px);
        }

        .pos-product-card:disabled {
            opacity: 0.55;
            box-shadow: 2px 2px 0 var(--nb-ink);
            cursor: not-allowed;
        }

        .pos-product-cat {
            color: var(--nb-muted);
            font-family: var(--nb-font-mono);
            font-size: 0.62rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .pos-product-name {
            font-size: 1rem;
            font-weight: 700;
            line-height: 1.2;
            overflow-wrap: anywhere;
        }

        .pos-price {
            margin-top: auto;
            padding-top: 0.45rem;
            font-family: var(--nb-font-mono);
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: -0.03em;
            white-space: nowrap;
        }

        .pos-stock {
            color: var(--nb-muted);
            font-size: 0.72rem;
        }

        .pos-product-out {
            font-family: var(--nb-font-mono);
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .pos-cart-tools {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            margin-left: auto;
        }

        .pos-clear {
            padding: 0.1rem 0;
            border: 0;
            border-bottom: 2px solid var(--nb-red-ink);
            background: none;
            color: var(--nb-red-ink);
            cursor: pointer;
            font-family: var(--nb-font-mono);
            font-size: 0.66rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .pos-cart-list {
            display: flex;
            flex-direction: column;
            max-height: 18rem;
            padding-right: 0.25rem;
            overflow-y: auto;
        }

        .pos-cart-item {
            display: grid;
            grid-template-areas:
                'info subtotal'
                'controls controls';
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 0.45rem 0.75rem;
            padding: 0.7rem 0;
            border-bottom: 2px dashed var(--nb-ink);
        }

        .pos-cart-item:first-child {
            border-top: 2px dashed var(--nb-ink);
        }

        .pos-cart-info {
            display: flex;
            flex-direction: column;
            grid-area: info;
            min-width: 0;
        }

        .pos-cart-name {
            font-size: 0.92rem;
            font-weight: 700;
            line-height: 1.2;
            overflow-wrap: anywhere;
        }

        .pos-cart-unit {
            color: var(--nb-muted);
            font-family: var(--nb-font-mono);
            font-size: 0.68rem;
        }

        .pos-cart-subtotal {
            grid-area: subtotal;
            font-family: var(--nb-font-mono);
            font-size: 0.82rem;
            font-weight: 700;
            text-align: right;
        }

        .pos-cart-controls {
            display: flex;
            align-items: center;
            grid-area: controls;
            gap: 0.4rem;
        }

        .pos-qty-btn {
            width: 2.75rem;
            height: 2.75rem;
            border: 3px solid var(--nb-ink);
            border-radius: 3px;
            background: var(--nb-surface);
            color: var(--nb-ink);
            box-shadow: 2px 2px 0 var(--nb-ink);
            cursor: pointer;
            font-family: var(--nb-font-mono);
            font-size: 1.1rem;
            font-weight: 700;
            line-height: 1;
        }

        .pos-qty-btn:active {
            box-shadow: 0 0 0 var(--nb-ink);
            transform: translate(2px, 2px);
        }

        .qty {
            min-width: 2rem;
            font-family: var(--nb-font-mono);
            font-size: 1rem;
            font-weight: 700;
            text-align: center;
        }

        .pos-remove-btn {
            width: 2.75rem;
            height: 2.75rem;
            margin-left: auto;
            border: 2px solid var(--nb-ink);
            border-radius: 3px;
            background: var(--nb-red);
            color: #0b0b0b;
            box-shadow: 2px 2px 0 var(--nb-ink);
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 700;
            line-height: 1;
        }

        .pos-remove-btn:active {
            box-shadow: 0 0 0 var(--nb-ink);
            transform: translate(2px, 2px);
        }

        .pos-total-row {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 1rem;
            margin-top: 0.5rem;
            padding-top: 0.9rem;
            border-top: 3px solid var(--nb-ink);
        }

        .pos-total-row > span:first-child {
            font-family: var(--nb-font-display);
            font-size: 1rem;
            text-transform: uppercase;
        }

        .cart-total {
            font-family: var(--nb-font-mono);
            font-size: clamp(1.35rem, 3vw, 1.75rem);
            font-weight: 700;
            letter-spacing: -0.03em;
        }

        .pos-btn-primary,
        .pos-btn-secondary {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 3.25rem;
            padding: 0.85rem 1rem;
            border: 3px solid var(--nb-ink);
            border-radius: var(--nb-radius);
            cursor: pointer;
            font-family: var(--nb-font-display);
            font-size: 0.95rem;
            font-weight: 400;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            transition: transform 0.1s ease, box-shadow 0.1s ease, background-color 0.1s ease;
        }

        .pos-btn-primary {
            margin-top: 1rem;
            background: var(--nb-blue);
            color: var(--nb-on-blue);
            box-shadow: var(--nb-shadow);
        }

        .pos-btn-primary:hover:not(:disabled) {
            box-shadow: 8px 8px 0 var(--nb-ink);
            transform: translate(-2px, -2px);
        }

        .pos-btn-primary:active:not(:disabled) {
            box-shadow: 2px 2px 0 var(--nb-ink);
            transform: translate(4px, 4px);
        }

        .pos-btn-primary:disabled {
            opacity: 0.55;
            box-shadow: 3px 3px 0 var(--nb-ink);
            cursor: not-allowed;
        }

        .pos-btn-secondary {
            margin-top: 0.75rem;
            background: var(--nb-surface);
            color: var(--nb-ink);
            box-shadow: var(--nb-shadow-sm);
        }

        .pos-btn-secondary:hover {
            background: var(--nb-red);
            color: #0b0b0b;
            box-shadow: var(--nb-shadow);
            transform: translate(-2px, -2px);
        }

        .pos-btn-secondary:active {
            box-shadow: 1px 1px 0 var(--nb-ink);
            transform: translate(3px, 3px);
        }

        .pos-btn-secondary.reset-transaction {
            background: var(--nb-green);
            color: #0b0b0b;
        }

        .pos-status-stamp {
            flex: none;
            display: inline-flex;
            align-items: center;
            padding: 0.2rem 0.55rem;
            border: 2px solid var(--nb-ink);
            border-radius: 2px;
            background: var(--nb-surface);
            color: var(--nb-ink);
            box-shadow: 2px 2px 0 var(--nb-ink);
            font-family: var(--nb-font-display);
            font-size: 0.68rem;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            transform: rotate(2deg);
        }

        .pos-status-pending .pos-status-stamp {
            background: var(--nb-yellow);
            color: #0b0b0b;
        }

        .pos-status-processing .pos-status-stamp {
            background: var(--nb-blue);
            color: var(--nb-on-blue);
        }

        .pos-status-success .pos-status-stamp {
            background: var(--nb-green);
            color: #0b0b0b;
            animation: pos-stamp 0.35s cubic-bezier(0.25, 1, 0.5, 1);
        }

        .pos-status-failed .pos-status-stamp {
            background: var(--nb-red);
            color: #0b0b0b;
            animation: pos-stamp 0.35s cubic-bezier(0.25, 1, 0.5, 1);
        }

        @keyframes pos-stamp {
            from {
                opacity: 0.35;
                transform: rotate(2deg) scale(1.45);
            }
            to {
                opacity: 1;
                transform: rotate(2deg) scale(1);
            }
        }

        .pos-status-body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 13.5rem;
        }

        .pos-empty {
            margin: 0;
            padding: 2.25rem 0.75rem;
            color: var(--nb-muted);
            text-align: center;
        }

        .pos-empty-icon {
            display: grid;
            width: 3.6rem;
            height: 3.6rem;
            margin: 0 auto 0.9rem;
            border: var(--nb-border);
            border-radius: 3px;
            background: var(--nb-yellow);
            color: #0b0b0b;
            box-shadow: var(--nb-shadow-sm);
            place-items: center;
            transform: rotate(-2deg);
        }

        .pos-empty-icon svg {
            width: 1.9rem;
            height: 1.9rem;
        }

        .pos-empty-title {
            margin: 0;
            color: var(--nb-ink);
            font-family: var(--nb-font-display);
            font-size: 1rem;
            letter-spacing: -0.01em;
        }

        .pos-empty-hint {
            margin: 0.35rem 0 0;
            font-size: 0.85rem;
        }

        .pos-state {
            padding: 1rem 0 0.25rem;
            text-align: center;
        }

        .pos-spinner {
            width: 3rem;
            height: 3rem;
            margin: 0 auto 1rem;
            border: 3px solid var(--nb-ink);
            border-top-color: transparent;
            border-radius: 50%;
            background: var(--nb-yellow);
            animation: pos-spin 0.9s linear infinite;
        }

        .pos-spinner-blue {
            background: var(--nb-blue);
        }

        @keyframes pos-spin {
            to {
                transform: rotate(360deg);
            }
        }

        .pos-state-icon {
            display: grid;
            width: 4rem;
            height: 4rem;
            margin: 0 auto 1rem;
            border: var(--nb-border);
            border-radius: 3px;
            color: #0b0b0b;
            box-shadow: var(--nb-shadow-sm);
            place-items: center;
            transform: rotate(-2deg);
        }

        .pos-state-icon-success {
            background: var(--nb-green);
        }

        .pos-state-icon-failed {
            background: var(--nb-red);
        }

        .pos-state-icon svg {
            width: 2rem;
            height: 2rem;
        }

        .pos-state-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            line-height: 1.3;
        }

        .pos-state-hint {
            margin: 0.35rem 0 0;
            color: var(--nb-muted);
            font-size: 0.85rem;
        }

        .pos-state-success {
            color: var(--nb-green-ink);
        }

        .pos-state-failed {
            color: var(--nb-red-ink);
        }

        .pos-card-info {
            display: grid;
            gap: 0.2rem;
            margin-top: 0.9rem;
            padding: 0.7rem 0.8rem;
            border: 2px solid var(--nb-ink);
            border-radius: 3px;
            background: var(--nb-surface-2);
            font-size: 0.85rem;
            text-align: left;
        }

        .pos-mono {
            font-family: var(--nb-font-mono);
            font-size: 0.78rem;
            font-weight: 700;
        }

        .pos-summary {
            margin-top: 1rem;
            border-top: 2px dashed var(--nb-ink);
            text-align: left;
        }

        .pos-summary-row {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.45rem 0;
            border-bottom: 2px dashed var(--nb-ink);
            font-size: 0.88rem;
        }

        .pos-summary-row > span:last-child {
            font-family: var(--nb-font-mono);
            font-weight: 700;
            text-align: right;
        }

        .pos-summary-row:last-child {
            border-bottom: 0;
        }

        .pos-summary-total {
            font-family: var(--nb-font-display);
            font-size: 0.92rem;
            text-transform: uppercase;
        }

        .pos-success-text {
            color: var(--nb-green-ink);
        }

        .pos-danger-text {
            color: var(--nb-red-ink);
        }

        @media (max-width: 640px) {
            body:has(.kasir-pos-embedded) .fi-header {
                align-items: flex-start;
            }

            body:has(.kasir-pos-embedded) .fi-header::after {
                display: none;
            }

            body:has(.kasir-pos-embedded) .fi-header-subheading {
                font-size: 0.88rem;
            }

            .pos-panel {
                padding: 1rem;
            }

            .pos-console-state {
                margin-left: 0;
            }

            .pos-status-body {
                min-height: 12rem;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .kasir-pos-embedded *,
            .kasir-pos-embedded *::before,
            .kasir-pos-embedded *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>

    <div class="pos-page">
        @livewire(\App\Livewire\Kasir\KasirPos::class, ['embedded' => true])
    </div>

    <script>
        // Filament persists the sidebar state and may reopen it after a
        // Livewire navigation. The POS needs the full width on a phone, so
        // close it once the panel and every navigation settle.
        const closePosSidebar = () => {
            if (window.innerWidth < 1024) {
                window.Alpine?.store('sidebar')?.close()
            }
        }

        document.addEventListener('alpine:initialized', () => {
            closePosSidebar()
            setTimeout(closePosSidebar, 250)
        })
        document.addEventListener('livewire:navigated', closePosSidebar)
    </script>
</x-filament-panels::page>
