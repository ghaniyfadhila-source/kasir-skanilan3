<x-filament-panels::page>
    <style>
        .kasir-pos-embedded {
            color: #1f2937;
        }

        .kasir-pos-embedded .pos-console-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            padding: 0.5rem 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            background: #f9fafb;
            font-size: 0.8125rem;
        }

        .kasir-pos-embedded .pos-console-dot {
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 9999px;
            background: #9ca3af;
        }

        .kasir-pos-embedded .pos-console-dot.is-online {
            background: #22c55e;
        }

        .kasir-pos-embedded .pos-console-name {
            font-weight: 600;
        }

        .kasir-pos-embedded .pos-console-state {
            color: #4b5563;
        }

        .kasir-pos-embedded .pos-console-state.is-online {
            color: #15803d;
        }

        .kasir-pos-embedded .pos-console-hint {
            color: #b45309;
        }

        .kasir-pos-embedded .pos-grid {
            display: grid;
            gap: 1.25rem;
            align-items: start;
        }

        @media (min-width: 1024px) {
            .kasir-pos-embedded .pos-grid {
                grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
            }
        }

        .kasir-pos-embedded .pos-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            padding: 1rem;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.04);
        }

        .kasir-pos-embedded .pos-card + .pos-card {
            margin-top: 1rem;
        }

        .kasir-pos-embedded .pos-card-title {
            margin: 0 0 1rem;
            font-size: 1.125rem;
            font-weight: 600;
            color: #111827;
        }

        .kasir-pos-embedded .pos-product-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
        }

        @media (min-width: 640px) {
            .kasir-pos-embedded .pos-product-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        .kasir-pos-embedded .pos-product-card {
            padding: 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            background: #ffffff;
            text-align: left;
            cursor: pointer;
            transition: background-color 0.15s ease;
        }

        .kasir-pos-embedded .pos-product-card:hover {
            background: #f9fafb;
        }

        .kasir-pos-embedded .pos-product-card:disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }

        .kasir-pos-embedded .pos-price {
            margin-top: 0.25rem;
            font-size: 1.125rem;
            font-weight: 700;
            color: #16a34a;
        }

        .kasir-pos-embedded .pos-cart-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            max-height: 16rem;
            overflow-y: auto;
        }

        .kasir-pos-embedded .pos-cart-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            padding: 0.5rem;
            border-radius: 0.5rem;
            background: #f9fafb;
        }

        .kasir-pos-embedded .pos-total-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
            font-size: 1.125rem;
            font-weight: 700;
        }

        .kasir-pos-embedded .pos-btn-primary,
        .kasir-pos-embedded .pos-btn-secondary {
            display: block;
            width: 100%;
            padding: 0.75rem 1rem;
            border: 0;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.15s ease;
        }

        .kasir-pos-embedded .pos-btn-primary {
            margin-top: 1rem;
            background: #16a34a;
            color: #ffffff;
        }

        .kasir-pos-embedded .pos-btn-primary:hover:not(:disabled) {
            background: #15803d;
        }

        .kasir-pos-embedded .pos-btn-primary:disabled {
            cursor: not-allowed;
            opacity: 0.5;
        }

        .kasir-pos-embedded .pos-btn-secondary {
            margin-top: 0.5rem;
            background: #e5e7eb;
            color: #374151;
            font-weight: 500;
        }

        .kasir-pos-embedded .pos-btn-secondary:hover {
            background: #d1d5db;
        }

        .kasir-pos-embedded .pos-qty-btn {
            width: 1.5rem;
            height: 1.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.25rem;
            background: #ffffff;
            font-size: 0.75rem;
            line-height: 1;
            cursor: pointer;
        }

        .kasir-pos-embedded .pos-qty-btn:hover {
            background: #f3f4f6;
        }

        .kasir-pos-embedded .pos-spinner {
            width: 3rem;
            height: 3rem;
            margin: 0 auto;
            border: 4px solid #22c55e;
            border-top-color: transparent;
            border-radius: 9999px;
            animation: pos-spin 1s linear infinite;
        }

        .kasir-pos-embedded .pos-spinner.pos-spinner-blue {
            border-color: #3b82f6;
            border-top-color: transparent;
        }

        @keyframes pos-spin {
            to {
                transform: rotate(360deg);
            }
        }

        .kasir-pos-embedded .pos-state-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 4rem;
            height: 4rem;
            margin: 0 auto;
            border-radius: 9999px;
        }

        .kasir-pos-embedded .pos-state-icon-success {
            background: #dcfce7;
            color: #16a34a;
        }

        .kasir-pos-embedded .pos-state-icon-failed {
            background: #fee2e2;
            color: #dc2626;
        }

        .kasir-pos-embedded .pos-state-icon svg {
            width: 2rem;
            height: 2rem;
        }

        .kasir-pos-embedded .pos-card-info {
            margin-top: 0.75rem;
            padding: 0.75rem;
            border-radius: 0.5rem;
            background: #f0fdf4;
            text-align: left;
            font-size: 0.875rem;
            color: #166534;
        }

        .kasir-pos-embedded .text-gray-900 {
            color: #111827;
        }

        .kasir-pos-embedded .text-gray-300 {
            color: #d1d5db;
        }

        .kasir-pos-embedded .pos-state-success {
            color: #15803d;
            font-weight: 600;
        }

        .kasir-pos-embedded .pos-state-failed {
            color: #b91c1c;
            font-weight: 600;
        }

        .kasir-pos-embedded .pos-summary {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            margin-top: 1rem;
            font-size: 0.875rem;
        }

        .kasir-pos-embedded .pos-summary-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .kasir-pos-embedded .pos-summary-total {
            padding-top: 0.5rem;
            border-top: 1px solid #e5e7eb;
            font-weight: 600;
        }

        .kasir-pos-embedded .pos-empty {
            padding: 2rem 0;
            color: #6b7280;
            text-align: center;
        }

        .kasir-pos-embedded .pos-muted {
            color: #6b7280;
            font-size: 0.875rem;
        }

        .kasir-pos-embedded .pos-success-text {
            color: #15803d;
        }

        .kasir-pos-embedded .pos-danger-text {
            color: #dc2626;
        }
    </style>

    @livewire(\App\Livewire\Kasir\KasirPos::class, ['embedded' => true])
</x-filament-panels::page>
