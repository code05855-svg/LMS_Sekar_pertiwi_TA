@extends('layouts.app')

@section('judul', 'Ubah Siswa')

@section('konten')
<x-judul-halaman judul="Ubah akun siswa" :deskripsi="$akun->nama.' · '.$akun->nomor_induk" :kembali="route('admin.siswa.index')" label="Daftar siswa" />

<x-card class="max-w-3xl">
    <form method="POST" action="{{ route('admin.siswa.update', $akun) }}">
        @csrf
        @method('PUT')
        @include('admin.siswa._form')
        <div class="mt-6 flex justify-end gap-2">
            <a href="{{ route('admin.siswa.index') }}" class="btn-secondary">Batal</a>
            <button type="submit" data-memuat="Menyimpan..." class="btn-primary">Simpan perubahan</button>
        </div>
    </form>
</x-card>
@endsection
