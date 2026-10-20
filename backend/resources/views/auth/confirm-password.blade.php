<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Konfirmasi Password · {{ config('app.name', 'Kasir Cashless') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500;600&family=Fira+Sans:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    @vite(['resources/css/app.css'])
</head>

<body
    class="flex min-h-full flex-col items-center justify-center bg-background px-4 py-10 text-foreground sm:px-6 bg-[radial-gradient(60rem_60rem_at_12%_-10%,rgba(34,197,94,0.18),transparent),radial-gradient(50rem_50rem_at_110%_10%,rgba(71,85,105,0.40),transparent)]">

    <main class="w-full max-w-md">
        <div class="flex flex-col items-center text-center">
            <span
                class="flex h-14 w-14 items-center justify-center rounded-2xl bg-accent/15 text-accent ring-1 ring-accent/30">
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="2.5" y="5" width="19" height="14" rx="3" />
                    <path d="M2.5 10h19" />
                    <path d="M6.5 14.5h4" />
                    <path d="M14 14.5h3.5" />
                </svg>
            </span>

            <h1 class="mt-5 text-2xl font-semibold tracking-tight">Konfirmasi Password</h1>
            <p class="mt-2 text-sm text-muted-foreground">
                Ini adalah area aman. Harap konfirmasi password Anda sebelum melanjutkan.
            </p>
        </div>

        <div class="card mt-8 p-6 sm:p-8">
            @if ($errors->any())
                <div role="alert" tabindex="-1" class="rounded-xl border border-destructive/40 bg-destructive/10 p-4">
                    <ul class="space-y-1 text-sm text-destructive">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('password.confirm') }}" class="mt-2 space-y-5">
                @csrf

                <div>
                    <label for="password" class="block text-sm font-medium text-foreground">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password"
                        required placeholder="••••••••"
                        class="input mt-2">
                </div>

                <button type="submit" class="btn btn-primary w-full">
                    Konfirmasi
                </button>
            </form>
        </div>
    </main>

    <p class="mt-8 text-center text-xs text-muted-foreground">
        © {{ date('Y') }} · Project Kasir Cashless Skanilan
    </p>
</body>

</html>