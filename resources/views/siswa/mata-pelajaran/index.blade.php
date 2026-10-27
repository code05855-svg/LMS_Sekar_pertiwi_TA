@extends('layouts.app')

@section('judul', 'Mata Pelajaran')

@section('konten')
<x-judul-halaman judul="Mata pelajaran" :deskripsi="$kelas ? 'Kelas '.$kelas->nama.' · '.$kelas->tahun_ajaran.'. Pilih mata pelajaran untuk membuka materi dan tugas.' : 'Temukan materi sesuai mata pelajaran dan lanjutkan belajarmu.'" />

@if (! $kelas)
    <x-alert tipe="peringatan" judul="Kamu belum ditempatkan di kelas">Hubungi admin sekolah supaya akunmu ditempatkan di kelas. Setelah itu mata pelajaran muncul di sini.</x-alert>
@elseif ($daftar->isEmpty())
    <x-card><x-kosong judul="Belum ada mata pelajaran" deskripsi="Guru belum ditugaskan untuk kelasmu. Hubungi admin sekolah." ikon="buku" /></x-card>
@else
    <div x-data="{ cari: '' }">
        <div class="card mb-5 flex items-center gap-3 p-3">
            <div class="relative flex-1">
                <x-ikon nama="cari" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted" />
                <input type="search" x-model="cari" placeholder="Cari mata pelajaran atau guru..." class="input pl-10" aria-label="Cari mata pelajaran">
            </div>
            <span class="badge-info hidden shrink-0 sm:inline-flex">Kelas {{ $kelas->nama }}</span>
        </div>

        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($daftar as $p)
                @php
                    $kumpul = $p->tugas_count - $p->tugas_belum_dikumpulkan_count;
                    $persen = $p->tugas_count ? (int) round($kumpul / $p->tugas_count * 100) : 0;
                @endphp
                <a href="{{ route('siswa.mata-pelajaran.show', $p) }}" data-cari="{{ mb_strtolower($p->mataPelajaran->nama.' '.$p->guru->nama) }}"
                   x-show="cari.trim() === '' || $el.dataset.cari.includes(cari.trim().toLowerCase())"
                   class="card group flex flex-col overflow-hidden transition hover:-translate-y-0.5 hover:shadow-(--shadow-pop)">
                    <x-sampul-mapel :nama="$p->mataPelajaran->nama" class="h-36" />
                    <div class="flex flex-1 flex-col p-5">
                        <h2 class="text-lg font-bold tracking-tight text-ink">{{ $p->mataPelajaran->nama }}</h2>
                        <p class="mt-0.5 text-sm text-muted">{{ $p->guru->nama }}</p>

                        <p class="mt-4 text-xs text-muted">{{ $p->materi_count }} materi · {{ $p->tugas_count }} tugas</p>
                        <div class="mt-2 flex items-center gap-3">
                            <div class="h-2 flex-1 overflow-hidden rounded-full bg-line" role="progressbar" aria-valuenow="{{ $persen }}" aria-valuemin="0" aria-valuemax="100" aria-label="Tugas dikumpulkan">
                                <div class="h-full rounded-full {{ $persen >= 100 ? 'bg-success' : 'bg-navy' }}" style="width: {{ $persen }}%"></div>
                            </div>
                            <span class="w-10 text-right text-xs font-semibold text-ink">{{ $persen }}%</span>
                        </div>
                        <p class="mt-1 text-xs {{ $p->tugas_belum_dikumpulkan_count ? 'text-danger' : 'text-success' }}">
                            {{ $p->tugas_count === 0 ? 'Belum ada tugas' : ($p->tugas_belum_dikumpulkan_count ? $p->tugas_belum_dikumpulkan_count.' tugas belum dikumpulkan' : 'Semua tugas sudah dikumpulkan') }}
                        </p>

                        <span class="mt-5 inline-flex items-center gap-1.5 text-sm font-bold text-navy">Buka mata pelajaran <x-ikon nama="panah" class="size-4 transition group-hover:translate-x-0.5" /></span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
@endif
@endsection
