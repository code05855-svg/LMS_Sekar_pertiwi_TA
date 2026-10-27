@extends('layouts.app')

@section('judul', $topik->judul)

@section('konten')
@php
    $pg = $topik->penugasanGuru;
    $pengguna = auth()->user();
    $moderator = $pengguna->can('moderasi', $topik);
    $pemilik = (int) $topik->user_id === (int) $pengguna->id;
@endphp

<x-judul-halaman :judul="$topik->judul" :deskripsi="$pg->mataPelajaran->nama.' · Kelas '.$pg->kelas->nama" :kembali="route('forum.topik.index', $pg)" label="Forum {{ $pg->mataPelajaran->nama }}">
    @if ($moderator)
        <form method="POST" action="{{ route('guru.forum.sematkan', $topik) }}">
            @csrf @method('PATCH')
            <button type="submit" class="btn-secondary btn-sm"><x-ikon nama="sematan" class="size-4" /> {{ $topik->disematkan ? 'Lepas sematan' : 'Sematkan' }}</button>
        </form>
        <form method="POST" action="{{ route('guru.forum.kunci', $topik) }}">
            @csrf @method('PATCH')
            <button type="submit" class="btn-secondary btn-sm"><x-ikon :nama="$topik->dikunci ? 'buka-kunci' : 'kunci'" class="size-4" /> {{ $topik->dikunci ? 'Buka kunci' : 'Kunci topik' }}</button>
        </form>
    @endif
    @if ($pemilik)
        <a href="{{ route('forum.topik.edit', $topik) }}" class="btn-secondary btn-sm"><x-ikon nama="ubah" class="size-4" /> Ubah</a>
    @endif
    @if ($pemilik || $moderator)
        <form method="POST" action="{{ route('forum.topik.destroy', $topik) }}" data-konfirmasi="Topik “{{ $topik->judul }}” beserta semua balasannya akan dihapus permanen." data-judul="Hapus topik?" data-tombol="Hapus">
            @csrf @method('DELETE')
            <button type="submit" class="btn-danger-soft btn-sm"><x-ikon nama="hapus" class="size-4" /> Hapus</button>
        </form>
    @endif
</x-judul-halaman>

<div class="grid gap-5 lg:grid-cols-3">
    <div class="space-y-5 lg:col-span-2">
        <section class="card p-5 sm:p-6">
            <div class="flex flex-wrap items-center gap-2">
                @if ($topik->disematkan)<span class="badge-gold"><x-ikon nama="sematan" class="size-3" /> Disematkan</span>@endif
                @if ($topik->dikunci)<span class="badge-muted"><x-ikon nama="kunci" class="size-3" /> Dikunci</span>@endif
            </div>
            <div class="mt-3 flex items-center gap-3">
                <x-avatar :nama="$topik->pembuat->nama" ukuran="size-11 text-sm" />
                <div>
                    <p class="font-semibold text-ink">{{ $topik->pembuat->nama }}@if ($topik->pembuat->adalahGuru()) <span class="badge-gold ml-1">Guru</span>@endif</p>
                    <p class="text-xs text-muted">{{ $topik->created_at->translatedFormat('d M Y, H:i') }}</p>
                </div>
            </div>
            <p class="mt-4 whitespace-pre-line break-words text-[15px] leading-relaxed text-ink">{{ $topik->isi }}</p>
        </section>

        <section class="card overflow-hidden" aria-labelledby="judul-balasan">
            <h2 id="judul-balasan" class="px-5 pb-3 pt-5 text-lg font-bold tracking-tight text-ink sm:px-6 sm:pt-6">{{ $balasan->total() }} balasan</h2>

            @if ($balasan->isEmpty())
                <x-kosong judul="Belum ada balasan" deskripsi="Jadilah yang pertama menjawab." ikon="forum" class="!py-8" />
            @else
                <div class="divide-y divide-line border-t border-line">
                    @foreach ($balasan as $b)
                        @include('forum.topik.partials.balasan', ['balasan' => $b])
                    @endforeach
                </div>
                @if ($balasan->hasPages())<div class="border-t border-line px-5 py-4 sm:px-6">{{ $balasan->links() }}</div>@endif
            @endif

            @include('forum.topik.partials.form-balasan')
        </section>
    </div>

    <div>@include('forum.topik.partials.panduan')</div>
</div>
@endsection
