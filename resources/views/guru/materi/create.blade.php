@extends('layouts.app')

@section('judul', 'Buat Materi')

@section('konten')
<x-judul-halaman judul="Buat materi" deskripsi="Unggah PDF dan tentukan kapan siswa boleh melihatnya." :kembali="route('guru.materi.index')" label="Daftar materi" />

@if ($daftarPenugasan->isEmpty())
    <x-alert tipe="peringatan" judul="Belum ada kelas yang diampu">Admin sekolah perlu menambahkan penugasan mengajar untuk akunmu sebelum kamu bisa membuat materi.</x-alert>
@else
    <form method="POST" action="{{ route('guru.materi.store') }}" enctype="multipart/form-data" class="card max-w-3xl p-5 sm:p-7">
        @csrf
        @include('guru.materi._form')

        <div class="mt-7 flex flex-wrap justify-end gap-3 border-t border-line pt-5">
            <a href="{{ route('guru.materi.index') }}" class="btn-secondary">Batal</a>
            <button type="submit" class="btn-primary" data-memuat="Menyimpan dan mengunggah...">Simpan materi</button>
        </div>
    </form>
@endif
@endsection
