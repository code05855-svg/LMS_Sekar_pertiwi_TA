@extends('layouts.app')

@section('judul', $tugas->judul)

@section('konten')
@php
    $pg = $tugas->penugasanGuru;
    $sisa = now()->diffInHours($tugas->tenggat, false);
@endphp

<x-judul-halaman :judul="$tugas->judul" :deskripsi="$pg->mataPelajaran->nama.' · Kelas '.$pg->kelas->nama.' · '.$pg->guru->nama" :kembali="route('siswa.mata-pelajaran.show', $pg)" label="{{ $pg->mataPelajaran->nama }}" />

<div class="grid gap-5 lg:grid-cols-3">
    <div class="space-y-5 lg:col-span-2">
        <x-card>
            <div class="rounded-2xl p-5 {{ ! $pengumpulan && $tugas->tenggat->isPast() ? 'bg-danger-soft' : 'bg-info-soft' }}">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-navy">Tenggat</p>
                        <p class="mt-1 text-xl font-extrabold text-navy">{{ $tugas->tenggat->translatedFormat('l, d F Y · H:i') }}</p>
                        <p class="mt-0.5 text-sm {{ $tugas->tenggat->isPast() ? 'text-danger' : ($sisa < 48 ? 'font-semibold text-danger' : 'text-muted') }}">
                            {{ $tugas->tenggat->isPast() ? 'Sudah lewat '.$tugas->tenggat->diffForHumans(null, true) : 'Sisa '.$tugas->tenggat->diffForHumans(null, true) }}
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">@include('siswa.partials.status-tugas', ['tugas' => $tugas, 'pengumpulan' => $pengumpulan])</div>
                </div>
            </div>

            <h2 class="mt-6 text-lg font-bold text-ink">Instruksi</h2>
            @if (filled($tugas->instruksi))
                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-ink">{{ $tugas->instruksi }}</p>
            @else
                <p class="mt-2 text-sm text-muted">Gurumu tidak menambahkan instruksi tertulis. Tanyakan di forum bila ada yang kurang jelas.</p>
            @endif

            <p class="mt-5 text-xs text-muted">Nilai maksimum {{ $tugas->nilai_maksimum }} · diberikan {{ $tugas->created_at->translatedFormat('d M Y') }}</p>
        </x-card>

        @if ($pengumpulan?->telahDinilai())
            <x-card judul="Hasil penilaian">
                <div class="flex flex-wrap items-center gap-5">
                    <div class="grid size-24 shrink-0 place-items-center rounded-2xl bg-success-soft text-center">
                        <div>
                            <p class="text-3xl font-extrabold leading-none text-success">{{ \App\Support\Format::nilai($pengumpulan->nilai) }}</p>
                            <p class="mt-1 text-[11px] text-muted">dari {{ $tugas->nilai_maksimum }}</p>
                        </div>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted">Komentar guru</p>
                        @if (filled($pengumpulan->komentar))
                            <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-ink">{{ $pengumpulan->komentar }}</p>
                        @else
                            <p class="mt-1 text-sm text-muted">Guru tidak menambahkan komentar.</p>
                        @endif
                        <p class="mt-3 text-xs text-muted">Dinilai {{ $pengumpulan->dinilai_pada->translatedFormat('d M Y, H:i') }}</p>
                    </div>
                </div>
            </x-card>
        @endif
    </div>

    <div>
        @include('siswa.tugas.partials.form-pengumpulan')
    </div>
</div>
@endsection
