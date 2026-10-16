<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Kasir Cashless') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    {{-- Do NOT add @livewireScriptConfig here: Livewire only auto-starts when
         window.livewireScriptConfig is undefined, and @livewireScripts alone
         already ships the CSRF token. With the config set, Livewire waits for
         something else to call Livewire.start() and the page never hydrates. --}}
</head>
<body class="font-sans antialiased">
    {{ $slot }}

    @livewireScripts
</body>
</html>