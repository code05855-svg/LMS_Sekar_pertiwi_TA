@extends('layouts.app')

@section('judul', $materi->judul)

@section('konten')
@php($pg = $materi->penugasanGuru)

<x-judul-halaman :judul="$materi->judul" :deskripsi="$pg->mataPelajaran->nama.' · Kelas '.$pg->kelas->nama.' · '.$pg->guru->nama" :kembali="route('siswa.mata-pelajaran.show', $pg)" label="{{ $pg->mataPelajaran->nama }}" />

<div class="grid gap-5 xl:grid-cols-5">
    <div class="card p-5 sm:p-6 xl:col-span-3">
        <div class="rounded-2xl bg-info-soft p-6 sm:p-8">
            <p class="text-xs font-bold uppercase tracking-widest text-navy">Materi pembelajaran</p>
            <h2 class="mt-4 text-2xl font-extrabold leading-tight tracking-tight text-navy sm:text-3xl">{{ $materi->judul }}</h2>
            @if (filled($materi->deskripsi))
                <p class="mt-3 line-clamp-2 text-muted">{{ $materi->deskripsi }}</p>
            @endif
            <span class="mt-5 inline-flex items-center gap-1.5 rounded-full bg-white px-3.5 py-1.5 text-xs font-semibold text-navy">
                <x-ikon nama="dokumen" class="size-3.5" /> PDF · {{ $materi->berkasMateri->count() }} berkas
            </span>
        </div>

        <h3 class="mt-7 text-lg font-bold text-ink">Tentang materi</h3>
        @if (filled($materi->deskripsi))
            <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-muted">{{ $materi->deskripsi }}</p>
        @else
            <p class="mt-2 text-sm text-muted">Gurumu belum menambahkan deskripsi. Buka PDF di bawah untuk membaca materinya.</p>
        @endif

        <h3 class="mt-7 text-lg font-bold text-ink">Berkas materi</h3>
        @if ($materi->berkasMateri->isEmpty())
            <x-kosong judul="Belum ada berkas" deskripsi="Gurumu belum mengunggah PDF untuk materi ini." ikon="dokumen" class="!py-6" />
        @else
            <ul class="mt-3 space-y-3">
                @foreach ($materi->berkasMateri as $berkas)
                    <li class="flex flex-wrap items-center gap-3 rounded-xl border border-line p-3.5">
                        <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-danger-soft text-danger"><x-ikon nama="dokumen" class="size-5" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-ink">{{ $berkas->nama_asli }}</p>
                            <p class="text-xs text-muted">{{ \App\Support\Format::ukuran($berkas->ukuran) }}@if ($berkas->jumlah_halaman) · {{ $berkas->jumlah_halaman }} halaman @endif</p>
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('siswa.materi.unduh', [$berkas, 'lihat' => 1]) }}" target="_blank" rel="noopener" class="btn-secondary btn-sm"><x-ikon nama="lihat" class="size-4" /> Buka</a>
                            <a href="{{ route('siswa.materi.unduh', $berkas) }}" class="btn-primary btn-sm"><x-ikon nama="unduh" class="size-4" /> Unduh PDF</a>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <section class="card flex h-[34rem] flex-col overflow-hidden xl:sticky xl:top-24 xl:col-span-2 xl:h-[calc(100dvh-8rem)] xl:max-h-[44rem]" aria-label="Sekar AI, asisten akademik">
        <header class="flex items-center gap-3 bg-navy px-4 py-3.5 text-white">
            <span class="grid size-10 shrink-0 place-items-center rounded-full bg-gold text-navy"><x-ikon nama="robot" class="size-6" /></span>
            <div class="min-w-0 flex-1 leading-tight">
                <p class="font-bold">Sekar AI</p>
                <p class="text-xs text-white/70">Asisten akademik</p>
            </div>
            <span class="rounded-full bg-success px-2.5 py-1 text-[11px] font-semibold">Online</span>
        </header>
        <x-chatbot-kotak id="materi" class="flex-1" :muat-awal="true" :saran="collect(['Jelaskan inti materi '.$materi->judul, 'Berikan contoh soal', 'Apa istilah penting di materi ini?'])" />
    </section>
</div>
@endsection
