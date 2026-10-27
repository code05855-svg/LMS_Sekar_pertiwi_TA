@php
    $pengguna = auth()->user();
    $jam = now()->hour;
    $sapaan = match (true) {
        $jam < 11 => 'Selamat pagi',
        $jam < 15 => 'Selamat siang',
        $jam < 18 => 'Selamat sore',
        default => 'Selamat malam',
    };
    $namaDepan = \Illuminate\Support\Str::of($pengguna->nama)->explode(' ')->first();
    $subjudul = match ($pengguna->peran) {
        \App\Enums\PeranPengguna::Admin => 'Kelola data dasar sekolah dengan rapi.',
        \App\Enums\PeranPengguna::Guru => 'Kelola kelas, materi, tugas, dan penilaian siswa.',
        default => 'Semangat belajar dan raih prestasi hari ini.',
    };
@endphp

<header class="sticky top-0 z-20 border-b border-line bg-white/90 backdrop-blur">
    <div class="mx-auto flex h-[68px] max-w-7xl items-center gap-3 px-4 sm:px-6 lg:px-8">
        <button type="button" class="btn-icon -ml-2 lg:hidden" @click="menu = true" aria-label="Buka menu">
            <x-ikon nama="menu" class="size-6" />
        </button>

        <div class="min-w-0 flex-1">
            <p class="truncate text-lg font-bold tracking-tight text-ink sm:text-xl">{{ $sapaan }}, {{ $namaDepan }}</p>
            <p class="hidden truncate text-xs text-muted sm:block">{{ $subjudul }}</p>
        </div>

        <a href="{{ route('profil.edit') }}" class="flex items-center gap-3 rounded-xl py-1 pl-1 pr-2 transition hover:bg-canvas" title="Profil saya">
            <x-avatar :nama="$pengguna->nama" ukuran="size-10 text-sm" />
            <span class="hidden text-left leading-tight sm:block">
                <span class="block max-w-44 truncate text-sm font-semibold text-ink">{{ $pengguna->nama }}</span>
                <span class="block text-xs text-muted">{{ $pengguna->peran->label() }}@if ($pengguna->adalahSiswa() && $pengguna->kelas_id) · {{ $pengguna->kelas->nama }}@endif</span>
            </span>
        </a>
    </div>
</header>
