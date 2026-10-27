@extends('layouts.app')

@section('judul', 'Tambah Siswa')

@section('konten')
<x-judul-halaman judul="Tambah akun siswa" deskripsi="Akun baru langsung bisa dipakai login dengan nomor induk dan password ini." :kembali="route('admin.siswa.index')" label="Daftar siswa" />

<x-card class="max-w-3xl">
    <form method="POST" action="{{ route('admin.siswa.store') }}">
        @csrf
        @include('admin.siswa._form')
        <div class="mt-6 flex justify-end gap-2">
            <a href="{{ route('admin.siswa.index') }}" class="btn-secondary">Batal</a>
            <button type="submit" data-memuat="Menyimpan..." class="btn-primary">Simpan akun</button>
        </div>
    </form>
</x-card>
@endsection
