@extends('layouts.app')

@section('judul', 'Ubah Guru')

@section('konten')
<x-judul-halaman judul="Ubah akun guru" :deskripsi="$akun->nama.' · '.$akun->nomor_induk" :kembali="route('admin.guru.index')" label="Daftar guru" />

<x-card class="max-w-3xl">
    <form method="POST" action="{{ route('admin.guru.update', $akun) }}">
        @csrf
        @method('PUT')
        @include('admin.guru._form')
        <div class="mt-6 flex justify-end gap-2">
            <a href="{{ route('admin.guru.index') }}" class="btn-secondary">Batal</a>
            <button type="submit" data-memuat="Menyimpan..." class="btn-primary">Simpan perubahan</button>
        </div>
    </form>
</x-card>
@endsection
