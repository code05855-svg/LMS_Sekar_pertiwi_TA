@extends('layouts.app')

@section('judul', 'Tambah Guru')

@section('konten')
<x-judul-halaman judul="Tambah akun guru" deskripsi="Akun baru langsung bisa dipakai login dengan nomor induk dan password ini." :kembali="route('admin.guru.index')" label="Daftar guru" />

<x-card class="max-w-3xl">
    <form method="POST" action="{{ route('admin.guru.store') }}">
        @csrf
        @include('admin.guru._form')
        <div class="mt-6 flex justify-end gap-2">
            <a href="{{ route('admin.guru.index') }}" class="btn-secondary">Batal</a>
            <button type="submit" data-memuat="Menyimpan..." class="btn-primary">Simpan akun</button>
        </div>
    </form>
</x-card>
@endsection
