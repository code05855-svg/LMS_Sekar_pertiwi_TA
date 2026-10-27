@extends('layouts.app')

@section('judul', 'Ubah Materi')

@section('konten')
<x-judul-halaman judul="Ubah materi" :deskripsi="$materi->judul" :kembali="route('guru.materi.show', $materi)" label="Detail materi" />

<form method="POST" action="{{ route('guru.materi.update', $materi) }}" enctype="multipart/form-data" class="card max-w-3xl p-5 sm:p-7">
    @csrf @method('PUT')
    @include('guru.materi._form')

    <div class="mt-7 flex flex-wrap justify-end gap-3 border-t border-line pt-5">
        <a href="{{ route('guru.materi.show', $materi) }}" class="btn-secondary">Batal</a>
        <button type="submit" class="btn-primary" data-memuat="Menyimpan...">Simpan perubahan</button>
    </div>
</form>
@endsection
