@extends('layouts.app')

@section('judul', 'Ubah Topik')

@section('konten')
<x-judul-halaman judul="Ubah topik" :deskripsi="$topik->judul" :kembali="route('forum.topik.show', $topik)" label="Kembali ke topik" />

<form method="POST" action="{{ route('forum.topik.update', $topik) }}" class="card max-w-3xl p-5 sm:p-7">
    @csrf @method('PUT')
    @include('forum.topik._form')

    <div class="mt-7 flex flex-wrap justify-end gap-3 border-t border-line pt-5">
        <a href="{{ route('forum.topik.show', $topik) }}" class="btn-secondary">Batal</a>
        <button type="submit" class="btn-primary" data-memuat="Menyimpan...">Simpan perubahan</button>
    </div>
</form>
@endsection
