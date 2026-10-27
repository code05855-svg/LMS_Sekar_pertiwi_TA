@extends('layouts.app')

@section('judul', 'Buat Topik')

@section('konten')
<x-judul-halaman judul="Buat topik diskusi" :deskripsi="$penugasan->mataPelajaran->nama.' · Kelas '.$penugasan->kelas->nama" :kembali="route('forum.topik.index', $penugasan)" label="Forum {{ $penugasan->mataPelajaran->nama }}" />

<div class="grid gap-5 lg:grid-cols-3">
    <form method="POST" action="{{ route('forum.topik.store', $penugasan) }}" class="card p-5 sm:p-7 lg:col-span-2">
        @csrf
        @include('forum.topik._form')

        <div class="mt-7 flex flex-wrap justify-end gap-3 border-t border-line pt-5">
            <a href="{{ route('forum.topik.index', $penugasan) }}" class="btn-secondary">Batal</a>
            <button type="submit" class="btn-primary" data-memuat="Memposting...">Posting topik</button>
        </div>
    </form>

    <div>@include('forum.topik.partials.panduan')</div>
</div>
@endsection
