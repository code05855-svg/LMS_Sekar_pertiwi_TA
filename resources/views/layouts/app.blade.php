<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('judul', 'Beranda') · {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-canvas" x-data="{ menu: false }" @keydown.escape.window="menu = false">
    <a href="#konten" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[70] focus:rounded-lg focus:bg-white focus:px-4 focus:py-2">Lewati ke konten</a>

    @include('layouts.partials.sidebar')

    <div class="lg:pl-64">
        @include('layouts.partials.navbar')

        <main id="konten" class="mx-auto w-full max-w-7xl px-4 pb-28 pt-6 sm:px-6 lg:px-8">
            @include('layouts.partials.flash')
            @yield('konten')
        </main>
    </div>

    @auth
        @if (auth()->user()->punyaPeran(\App\Enums\PeranPengguna::Siswa, \App\Enums\PeranPengguna::Guru) && ! request()->routeIs('chatbot.*', 'siswa.materi.show'))
            <x-chatbot-widget />
        @endif
    @endauth

    {{-- Modal konfirmasi global untuk <form data-konfirmasi="..."> --}}
    <div x-data="konfirmasi" x-on:minta-konfirmasi.window="buka($event.detail)" x-on:keydown.escape.window="terbuka = false"
         x-show="terbuka" x-cloak class="fixed inset-0 z-[80] grid place-items-center p-4" role="alertdialog" aria-modal="true" aria-labelledby="judul-konfirmasi">
        <div x-show="terbuka" x-transition.opacity class="absolute inset-0 bg-navy-900/50" @click="terbuka = false"></div>
        <div x-show="terbuka" x-transition.scale.95 class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-(--shadow-pop)">
            <div class="flex gap-4">
                <span class="grid size-11 shrink-0 place-items-center rounded-full" :class="bahaya ? 'bg-danger-soft text-danger' : 'bg-info-soft text-navy'">
                    <x-ikon nama="peringatan" class="size-6" />
                </span>
                <div class="min-w-0">
                    <h2 id="judul-konfirmasi" class="text-lg font-bold text-ink" x-text="judul"></h2>
                    <p class="mt-1 text-sm leading-relaxed text-muted" x-text="pesan"></p>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" class="btn-secondary" @click="terbuka = false">Batal</button>
                <button type="button" :class="bahaya ? 'btn-danger' : 'btn-primary'" @click="lanjut()" x-text="tombol"></button>
            </div>
        </div>
    </div>

    @stack('skrip')
</body>
</html>
