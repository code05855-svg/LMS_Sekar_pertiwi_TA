@extends('layouts.app')

@section('judul', 'Nilai — '.$pengumpulan->siswa->nama)

@section('konten')
@php
    $tugas = $pengumpulan->tugas;
    $pg = $tugas->penugasanGuru;
    $bisaLihat = str_starts_with($pengumpulan->mime, 'image/') || $pengumpulan->mime === 'application/pdf';
    $urlLihat = route('guru.pengumpulan-tugas.unduh', [$pengumpulan, 'lihat' => 1]);
@endphp

<x-judul-halaman :judul="$pengumpulan->siswa->nama" :deskripsi="$tugas->judul.' · '.$pg->mataPelajaran->nama.' '.$pg->kelas->nama" :kembali="route('guru.pengumpulan-tugas.index', $tugas)" label="Daftar pengumpulan" />

<div class="grid gap-5 lg:grid-cols-5">
    <div class="space-y-5 lg:col-span-3">
        <x-card judul="Jawaban siswa">
            <div class="flex flex-wrap items-center gap-3 rounded-xl bg-canvas p-4">
                <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-info-soft text-navy"><x-ikon nama="dokumen" class="size-6" /></span>
                <div class="min-w-0 flex-1">
                    <p class="truncate font-semibold text-ink">{{ $pengumpulan->nama_asli }}</p>
                    <p class="text-xs text-muted">{{ \App\Support\Format::ukuran($pengumpulan->ukuran) }} · {{ $pengumpulan->waktu_kumpul->translatedFormat('d M Y, H:i') }}</p>
                </div>
                <x-badge-status :status="$pengumpulan->status" />
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                @if ($bisaLihat)
                    <a href="{{ $urlLihat }}" target="_blank" rel="noopener" class="btn-secondary btn-sm"><x-ikon nama="lihat" class="size-4" /> Buka di tab baru</a>
                @endif
                <a href="{{ route('guru.pengumpulan-tugas.unduh', $pengumpulan) }}" class="btn-primary btn-sm"><x-ikon nama="unduh" class="size-4" /> Unduh</a>
            </div>

            @if ($pengumpulan->mime === 'application/pdf')
                <iframe src="{{ $urlLihat }}" title="Pratinjau {{ $pengumpulan->nama_asli }}" class="mt-4 h-[32rem] w-full rounded-xl border border-line bg-canvas"></iframe>
            @elseif (str_starts_with($pengumpulan->mime, 'image/'))
                <img src="{{ $urlLihat }}" alt="Jawaban {{ $pengumpulan->siswa->nama }}" class="mt-4 max-h-[32rem] w-full rounded-xl border border-line bg-canvas object-contain">
            @else
                <p class="mt-4 text-sm text-muted">Pratinjau tidak tersedia untuk jenis berkas ini. Unduh untuk memeriksanya.</p>
            @endif
        </x-card>

        <x-card judul="Tugas">
            <p class="font-semibold text-ink">{{ $tugas->judul }}</p>
            <p class="text-xs text-muted">Tenggat {{ $tugas->tenggat->translatedFormat('d M Y, H:i') }} · nilai maksimum {{ $tugas->nilai_maksimum }}</p>
            @if (filled($tugas->instruksi))
                <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-muted">{{ $tugas->instruksi }}</p>
            @endif
        </x-card>
    </div>

    <div class="lg:col-span-2">
        @include('guru.pengumpulan-tugas.partials.form-nilai')
    </div>
</div>
@endsection
