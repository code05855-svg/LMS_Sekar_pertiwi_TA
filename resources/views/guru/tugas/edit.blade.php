@extends('layouts.app')

@section('judul', 'Ubah Tugas')

@section('konten')
<x-judul-halaman judul="Ubah tugas" :deskripsi="$tugas->judul" :kembali="route('guru.tugas.show', $tugas)" label="Detail tugas" />

<form method="POST" action="{{ route('guru.tugas.update', $tugas) }}" class="card max-w-3xl p-5 sm:p-7">
    @csrf @method('PUT')
    @include('guru.tugas._form')

    <div class="mt-7 flex flex-wrap justify-end gap-3 border-t border-line pt-5">
        <a href="{{ route('guru.tugas.show', $tugas) }}" class="btn-secondary">Batal</a>
        <button type="submit" class="btn-primary" data-memuat="Menyimpan...">Simpan perubahan</button>
    </div>
</form>
@endsection
