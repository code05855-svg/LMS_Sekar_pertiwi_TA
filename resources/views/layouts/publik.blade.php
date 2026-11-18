<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul', $profil->nama) · {{ $profil->nama }}</title>
    <meta name="description" content="@yield('deskripsi', $profil->tagline.' '.$profil->deskripsi)">
    <meta property="og:site_name" content="{{ $profil->nama }}">
    <meta property="og:title" content="@yield('judul', $profil->nama)">
    <meta property="og:description" content="@yield('deskripsi', $profil->tagline)">
    <meta property="og:image" content="@yield('gambar', asset('images/login-sekolah.jpg'))">
    <meta property="og:type" content="website">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white" x-data="{ buka: false }" @keydown.escape.window="buka = false">
    <a href="#konten" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[70] focus:rounded-lg focus:bg-white focus:px-4 focus:py-2">Lewati ke konten</a>
 
    @php
        $menu = [
            ['Beranda', route('beranda'), request()->routeIs('beranda')],
            ['Tentang', route('beranda').'#tentang', false],
            ['Informasi', route('beranda.informasi.index'), request()->routeIs('beranda.informasi.*')],
            ['Kegiatan', route('beranda.kegiatan.index'), request()->routeIs('beranda.kegiatan.*')],
            ['Kontak', route('beranda').'#kontak', false],
        ];
    @endphp
 
    <header class="sticky top-0 z-40 border-b border-line bg-white/90 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('beranda') }}" class="flex min-w-0 items-center gap-3">
                <img src="{{ asset('images/logo.png') }}" alt="Lambang {{ $profil->nama }}" class="size-10 shrink-0">
                <span class="min-w-0 leading-tight">
                    <span class="block truncate text-[15px] font-extrabold uppercase tracking-wide text-navy">{{ $profil->nama }}</span>
                    <span class="hidden truncate text-[11px] font-semibold text-muted sm:block">{{ $profil->tagline }}</span>
                </span>
            </a>
 
            <nav class="hidden items-center gap-1 md:flex" aria-label="Navigasi beranda">
                @foreach ($menu as [$label, $url, $aktif])
                    <a href="{{ $url }}" @if ($aktif) aria-current="page" @endif
                       class="rounded-lg px-3 py-2 text-sm font-semibold transition {{ $aktif ? 'bg-info-soft text-navy' : 'text-ink/80 hover:bg-canvas hover:text-navy' }}">{{ $label }}</a>
                @endforeach
            </nav>
 
            <div class="flex items-center gap-2">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-primary hidden sm:inline-flex"><x-ikon nama="dashboard" class="size-4" /> Buka dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn-primary hidden sm:inline-flex">Masuk ke LMS</a>
                @endauth
                <button type="button" class="btn-icon md:hidden" @click="buka = !buka" :aria-expanded="buka" aria-label="Buka menu">
                    <x-ikon nama="menu" class="size-6" />
                </button>
            </div>
        </div>
 
        {{-- Menu ponsel --}}
        <div x-show="buka" x-cloak x-transition class="border-t border-line bg-white px-4 pb-4 pt-2 md:hidden">
            <nav class="grid gap-1" aria-label="Navigasi beranda (ponsel)">
                @foreach ($menu as [$label, $url, $aktif])
                    <a href="{{ $url }}" @click="buka = false" class="rounded-lg px-3 py-2.5 text-sm font-semibold {{ $aktif ? 'bg-info-soft text-navy' : 'text-ink/80 hover:bg-canvas' }}">{{ $label }}</a>
                @endforeach
            </nav>
            @auth
                <a href="{{ route('dashboard') }}" class="btn-primary mt-3 w-full">Buka dashboard</a>
            @else
                <a href="{{ route('login') }}" class="btn-primary mt-3 w-full">Masuk ke LMS</a>
            @endauth
        </div>
    </header>
 
    <main id="konten">
        @yield('konten')
    </main>
 
    <footer class="bg-navy-900 text-white">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-3 lg:px-8">
            <div>
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/logo.png') }}" alt="" class="size-12">
                    <span class="text-lg font-extrabold uppercase tracking-wide">{{ $profil->nama }}</span>
                </div>
                <p class="mt-4 max-w-sm text-sm leading-relaxed text-white/75">{{ $profil->deskripsi }}</p>
            </div>
 
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-gold">Tautan</p>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="{{ route('beranda') }}" class="text-white/80 hover:text-white">Beranda</a></li>
                    <li><a href="{{ route('beranda.informasi.index') }}" class="text-white/80 hover:text-white">Informasi sekolah</a></li>
                    <li><a href="{{ route('beranda.kegiatan.index') }}" class="text-white/80 hover:text-white">Kegiatan sekolah</a></li>
                    <li><a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="font-semibold text-gold hover:underline">{{ auth()->check() ? 'Buka dashboard' : 'Masuk ke LMS' }}</a></li>
                </ul>
            </div>
 
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-gold">Hubungi kami</p>
                <ul class="mt-4 space-y-2.5 text-sm text-white/80">
                    @if ($profil->ada('alamat'))<li class="flex gap-2.5"><x-ikon nama="sematan" class="mt-0.5 size-4 text-gold" /> <span>{{ $profil->alamat }}</span></li>@endif
                    @if ($profil->ada('telepon'))<li class="flex gap-2.5"><x-ikon nama="telepon" class="mt-0.5 size-4 text-gold" /> <a href="{{ $profil->urlTelepon() }}" class="hover:text-white">{{ $profil->telepon }}</a></li>@endif
                    @if ($profil->ada('email'))<li class="flex gap-2.5"><x-ikon nama="surel" class="mt-0.5 size-4 text-gold" /> <a href="{{ $profil->urlEmail() }}" class="break-all hover:text-white">{{ $profil->email }}</a></li>@endif
                    @if (! $profil->punyaKontak() && ! $profil->ada('alamat'))<li class="text-white/60">Informasi kontak akan segera tersedia.</li>@endif
                </ul>
                @if ($profil->sosial())
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($profil->sosial() as $nama => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="rounded-full bg-white/10 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-white/20">{{ $nama }}</a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        <div class="border-t border-white/10">
            <p class="mx-auto max-w-7xl px-4 py-5 text-xs text-white/55 sm:px-6 lg:px-8">© {{ date('Y') }} {{ $profil->nama }}. Seluruh hak dilindungi.</p>
        </div>
    </footer>
 
    @stack('skrip')
</body>
</html>
