@extends('layouts.app')

@section('judul', 'Forum '.$penugasan->mataPelajaran->nama)

@section('konten')
<x-judul-halaman :judul="'Forum '.$penugasan->mataPelajaran->nama" :deskripsi="'Kelas '.$penugasan->kelas->nama.' · '.$penugasan->guru->nama" :kembali="route('forum.index')" label="Semua forum">
    <a href="{{ route('forum.topik.create', $penugasan) }}" class="btn-primary"><x-ikon nama="tambah" class="size-4" /> Buat topik</a>
</x-judul-halaman>

<form method="GET" class="card mb-5 flex items-center gap-3 p-3">
    <div class="relative flex-1">
        <x-ikon nama="cari" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted" />
        <input type="search" name="cari" value="{{ request('cari') }}" placeholder="Cari topik diskusi..." class="input pl-10" aria-label="Cari topik">
    </div>
    <button class="btn-secondary" type="submit">Cari</button>
    @if (request()->filled('cari'))<a href="{{ route('forum.topik.index', $penugasan) }}" class="tautan text-sm">Atur ulang</a>@endif
</form>

<div class="grid gap-5 lg:grid-cols-3">
    <x-card judul="Diskusi terbaru" class="lg:col-span-2" :tanpa-padding="true">
        @if ($daftar->isEmpty())
            <x-kosong :judul="request()->filled('cari') ? 'Tidak ada topik yang cocok' : 'Belum ada diskusi'" :deskripsi="request()->filled('cari') ? 'Coba kata kunci lain.' : 'Mulai diskusi pertama untuk kelas ini.'" ikon="forum">
                @unless (request()->filled('cari'))<a href="{{ route('forum.topik.create', $penugasan) }}" class="btn-primary btn-sm">Buat topik</a>@endunless
            </x-kosong>
        @else
            <ul class="divide-y divide-line">
                @foreach ($daftar as $t)
                    <li>
                        <a href="{{ route('forum.topik.show', $t) }}" class="flex items-start gap-4 px-5 py-4 transition hover:bg-canvas sm:px-6">
                            <x-avatar :nama="$t->pembuat->nama" ukuran="size-11 text-sm" />
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-semibold text-ink">{{ $t->judul }}</h3>
                                    @if ($t->disematkan)<span class="badge-gold"><x-ikon nama="sematan" class="size-3" /> Disematkan</span>@endif
                                    @if ($t->dikunci)<span class="badge-muted"><x-ikon nama="kunci" class="size-3" /> Dikunci</span>@endif
                                </div>
                                <p class="mt-0.5 line-clamp-1 text-sm text-muted">{{ $t->isi }}</p>
                                <p class="mt-1.5 text-xs text-muted">
                                    {{ $t->pembuat->nama }}@if ($t->pembuat->adalahGuru()) <span class="badge-info ml-1">Guru</span>@endif
                                    · <span class="font-semibold text-navy">{{ $t->balasan_count }} balasan</span> · {{ $t->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="px-5 pb-5 sm:px-6">{{ $daftar->links() }}</div>
        @endif
    </x-card>

    <div>@include('forum.topik.partials.panduan')</div>
</div>
@endsection
