<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · {{ config('app.name', 'Kasir Cashless') }}</title>

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

            <h1 class="mt-5 text-2xl font-semibold tracking-tight">Kasir Cashless</h1>
            <p class="mt-2 text-sm text-muted-foreground">
                Skanilan · sistem kasir RFID tanpa kontak
            </p>
        </div>

        <div class="card mt-8 p-6 sm:p-8">
            @if ($errors->any())
                <div role="alert" tabindex="-1" aria-labelledby="login-error-title"
                    class="rounded-xl border border-destructive/40 bg-destructive/10 p-4">
                    <h2 id="login-error-title" class="text-sm font-semibold text-foreground">
                        Tidak bisa masuk
                    </h2>
                    <ul class="mt-2 space-y-1 text-sm text-destructive">
                        @foreach ($errors->getMessages() as $field => $messages)
                            @foreach ($messages as $message)
                                <li>
                                    <a href="#{{ $field }}" class="underline underline-offset-2 hover:text-destructive/80">
                                        {{ $message }}
                                    </a>
                                </li>
                            @endforeach
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="mt-2 space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-foreground">Email</label>
                    <input id="email" name="email" type="email" autocomplete="email" required
                        value="{{ old('email') }}" placeholder="nama@sekolah.sch.id"
                        @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                        class="input mt-2">
                    @error('email')
                        <p id="email-error" class="mt-2 text-sm text-destructive">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-foreground">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password"
                        required placeholder="••••••••"
                        @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                        class="input mt-2">
                    @error('password')
                        <p id="password-error" class="mt-2 text-sm text-destructive">{{ $message }}</p>
                    @enderror
                </div>

                <label for="remember_me" class="flex cursor-pointer items-center gap-2 text-sm text-muted-foreground">
                    <input id="remember_me" name="remember" type="checkbox"
                        class="h-4 w-4 cursor-pointer rounded border-white/20 bg-background/60 accent-accent focus-visible:ring-2 focus-visible:ring-accent/40">
                    Ingat saya di perangkat ini
                </label>

                <button type="submit" class="btn btn-primary w-full">
                    Masuk
                </button>
            </form>
        </div>

        <div class="mt-6 rounded-xl border border-white/5 bg-background/40 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Akun demo</p>
            <ul class="mt-2 space-y-1 font-mono text-xs text-muted-foreground">
                <li>admin@skanilan.local · password123</li>
                <li>kasir@skanilan.local · password123</li>
            </ul>
        </div>
    </main>

    <p class="mt-8 text-center text-xs text-muted-foreground">
        © {{ date('Y') }} · Project Kasir Cashless Skanilan
    </p>
</body>

</html>