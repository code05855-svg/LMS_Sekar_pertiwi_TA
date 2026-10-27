<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('kode') · {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    @vite(['resources/css/app.css'])
</head>
<body class="grid min-h-screen place-items-center bg-canvas px-4">
    <div class="w-full max-w-lg text-center">
        <img src="{{ asset('images/logo.png') }}" alt="" class="mx-auto size-16">
        <p class="mt-6 text-7xl font-extrabold tracking-tight text-navy">@yield('kode')</p>
        <h1 class="mt-2 text-2xl font-bold text-ink">@yield('judul')</h1>
        <p class="mx-auto mt-3 max-w-md text-muted">@yield('pesan')</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="javascript:history.back()" class="btn-secondary">Kembali</a>
            <a href="{{ url('/') }}" class="btn-primary">Ke beranda</a>
        </div>
    </div>
</body>
</html>
