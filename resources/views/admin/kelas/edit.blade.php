@extends('layouts.app')

@section('judul', 'Ubah Kelas')

@section('konten')
<x-judul-halaman judul="Ubah kelas" :deskripsi="$kelas->tingkat->value.' '.$kelas->nama.' · '.$kelas->tahun_ajaran" :kembali="route('admin.kelas.show', $kelas)" label="Detail kelas" />

<x-card class="max-w-3xl">
    <form method="POST" action="{{ route('admin.kelas.update', $kelas) }}">
        @csrf
        @method('PUT')
        @include('admin.kelas._form')
        <div class="mt-6 flex justify-end gap-2">
            <a href="{{ route('admin.kelas.show', $kelas) }}" class="btn-secondary">Batal</a>
            <button type="submit" data-memuat="Menyimpan..." class="btn-primary">Simpan perubahan</button>
        </div>
    </form>
</x-card>
@endsection
