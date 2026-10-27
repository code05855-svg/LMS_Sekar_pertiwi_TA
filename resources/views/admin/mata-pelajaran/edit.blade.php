@extends('layouts.app')

@section('judul', 'Ubah Mata Pelajaran')

@section('konten')
<x-judul-halaman judul="Ubah mata pelajaran" :deskripsi="$mapel->kode.' · '.$mapel->nama" :kembali="route('admin.mata-pelajaran.index')" label="Daftar mata pelajaran" />

<x-card class="max-w-3xl">
    <form method="POST" action="{{ route('admin.mata-pelajaran.update', $mapel) }}">
        @csrf
        @method('PUT')
        @include('admin.mata-pelajaran._form')
        <div class="mt-6 flex justify-end gap-2">
            <a href="{{ route('admin.mata-pelajaran.index') }}" class="btn-secondary">Batal</a>
            <button type="submit" data-memuat="Menyimpan..." class="btn-primary">Simpan perubahan</button>
        </div>
    </form>
</x-card>
@endsection
