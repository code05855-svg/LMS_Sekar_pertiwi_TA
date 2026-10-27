@extends('layouts.app')

@section('judul', 'Buat Tugas')

@section('konten')
<x-judul-halaman judul="Buat tugas" deskripsi="Siswa kelas yang dipilih dapat langsung melihat dan mengumpulkannya." :kembali="route('guru.tugas.index')" label="Daftar tugas" />

@if ($daftarPenugasan->isEmpty())
    <x-alert tipe="peringatan" judul="Belum ada kelas yang diampu">Admin sekolah perlu menambahkan penugasan mengajar untuk akunmu sebelum kamu bisa membuat tugas.</x-alert>
@else
    <form method="POST" action="{{ route('guru.tugas.store') }}" class="card max-w-3xl p-5 sm:p-7">
        @csrf
        @include('guru.tugas._form')

        <div class="mt-7 flex flex-wrap justify-end gap-3 border-t border-line pt-5">
            <a href="{{ route('guru.tugas.index') }}" class="btn-secondary">Batal</a>
            <button type="submit" class="btn-primary" data-memuat="Menyimpan...">Simpan tugas</button>
        </div>
    </form>
@endif
@endsection
