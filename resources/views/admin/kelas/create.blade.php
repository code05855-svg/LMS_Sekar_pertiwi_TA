@extends('layouts.app')

@section('judul', 'Tambah Kelas')

@section('konten')
<x-judul-halaman judul="Tambah kelas" deskripsi="Setelah kelas dibuat, atur guru pengampu dan tempatkan siswa." :kembali="route('admin.kelas.index')" label="Daftar kelas" />

<x-card class="max-w-3xl">
    <form method="POST" action="{{ route('admin.kelas.store') }}">
        @csrf
        @include('admin.kelas._form')
        <div class="mt-6 flex justify-end gap-2">
            <a href="{{ route('admin.kelas.index') }}" class="btn-secondary">Batal</a>
            <button type="submit" data-memuat="Menyimpan..." class="btn-primary">Simpan kelas</button>
        </div>
    </form>
</x-card>
@endsection
