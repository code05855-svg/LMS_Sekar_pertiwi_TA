@extends('layouts.app')
 
@section('judul', 'Nilai — '.$pengumpulan->siswa->nama)
 
@section('konten')
@php
    $tugas = $pengumpulan->tugas;
    $pg = $tugas->penugasanGuru;
@endphp
 
<x-judul-halaman :judul="$pengumpulan->siswa->nama" :deskripsi="$tugas->judul.' · '.$pg->mataPelajaran->nama.' '.$pg->kelas->nama" :kembali="route('guru.pengumpulan-tugas.index', $tugas)" label="Daftar pengumpulan" />
 
<div class="grid gap-5 lg:grid-cols-5">
    <div class="space-y-5 lg:col-span-3">
        <x-card judul="Jawaban siswa" :deskripsi="'Pratinjau '.strtolower($pengumpulan->jenis->label()).' · nilai di panel sebelah kanan'">
            <x-pratinjau-jawaban :pengumpulan="$pengumpulan" :teks-pratinjau="$teksPratinjau"
                :url-lihat="$pengumpulan->berupaBerkas() ? route('guru.pengumpulan-tugas.unduh', [$pengumpulan, 'lihat' => 1]) : null"
                :url-unduh="$pengumpulan->berupaBerkas() ? route('guru.pengumpulan-tugas.unduh', $pengumpulan) : null" />
        </x-card>
 
        <x-card judul="Tugas">
            <p class="font-semibold text-ink">{{ $tugas->judul }}</p>
            <p class="text-xs text-muted">Tenggat {{ $tugas->tenggat->translatedFormat('d M Y, H:i') }} · nilai maksimum {{ $tugas->nilai_maksimum }}</p>
            @if (filled($tugas->instruksi))
                <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-muted">{{ $tugas->instruksi }}</p>
            @endif
            <x-daftar-lampiran :tugas="$tugas" class="mt-5" />
        </x-card>
    </div>
 
    <div class="lg:col-span-2">
        @include('guru.pengumpulan-tugas.partials.form-nilai')
    </div>
</div>
@endsection
