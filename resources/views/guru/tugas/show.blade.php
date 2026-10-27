@extends('layouts.app')

@section('judul', $tugas->judul)

@section('konten')
@php($pg = $tugas->penugasanGuru)

<x-judul-halaman :judul="$tugas->judul" :deskripsi="$pg->mataPelajaran->nama.' · Kelas '.$pg->kelas->nama" :kembali="route('guru.kelas.show', $pg)" label="{{ $pg->mataPelajaran->nama }} {{ $pg->kelas->nama }}">
    <a href="{{ route('guru.pengumpulan-tugas.index', $tugas) }}" class="btn-primary"><x-ikon nama="tugas" class="size-4" /> Lihat pengumpulan</a>
    <a href="{{ route('guru.tugas.edit', $tugas) }}" class="btn-secondary"><x-ikon nama="ubah" class="size-4" /> Ubah</a>
    <form method="POST" action="{{ route('guru.tugas.destroy', $tugas) }}" data-konfirmasi="Tugas “{{ $tugas->judul }}” beserta {{ $ringkasan['dikumpulkan'] }} jawaban dan nilainya akan dihapus permanen." data-judul="Hapus tugas?" data-tombol="Hapus">
        @csrf @method('DELETE')
        <button type="submit" class="btn-danger-soft"><x-ikon nama="hapus" class="size-4" /> Hapus</button>
    </form>
</x-judul-halaman>

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-kartu-stat label="Terkumpul" :nilai="$ringkasan['dikumpulkan'].'/'.$ringkasan['siswa']" catatan="Dari siswa di kelas" warna="navy" />
    <x-kartu-stat label="Terlambat" :nilai="$ringkasan['terlambat']" catatan="Dikumpulkan lewat tenggat" :warna="$ringkasan['terlambat'] ? 'danger' : 'success'" />
    <x-kartu-stat label="Sudah dinilai" :nilai="$ringkasan['dinilai'].'/'.$ringkasan['dikumpulkan']" catatan="Dari yang terkumpul" warna="gold" />
    <x-kartu-stat label="Rata-rata nilai" :nilai="\App\Support\Format::nilai($ringkasan['rata_rata'] !== null ? round($ringkasan['rata_rata'], 1) : null)" :catatan="'Dari maksimum '.$tugas->nilai_maksimum" warna="success" />
</div>

<div class="mt-5 grid gap-5 lg:grid-cols-3">
    <x-card judul="Instruksi" class="lg:col-span-2">
        @if (filled($tugas->instruksi))
            <p class="whitespace-pre-line text-sm leading-relaxed text-ink">{{ $tugas->instruksi }}</p>
        @else
            <p class="text-sm text-muted">Belum ada instruksi. <a href="{{ route('guru.tugas.edit', $tugas) }}" class="tautan">Tambahkan</a>.</p>
        @endif
    </x-card>

    <x-card judul="Detail">
        <dl class="space-y-4 text-sm">
            <div>
                <dt class="text-xs text-muted">Tenggat</dt>
                <dd class="font-semibold text-ink">{{ $tugas->tenggat->translatedFormat('l, d F Y · H:i') }}</dd>
                <dd class="text-xs {{ $tugas->tenggat->isPast() ? 'text-danger' : 'text-success' }}">{{ $tugas->tenggat->isPast() ? 'Sudah lewat ' : 'Sisa ' }}{{ $tugas->tenggat->diffForHumans(null, true) }}</dd>
            </div>
            <div><dt class="text-xs text-muted">Nilai maksimum</dt><dd class="font-semibold text-ink">{{ $tugas->nilai_maksimum }}</dd></div>
            <div><dt class="text-xs text-muted">Dibuat</dt><dd class="font-semibold text-ink">{{ $tugas->created_at->translatedFormat('d M Y, H:i') }}</dd></div>
        </dl>
    </x-card>
</div>
@endsection
