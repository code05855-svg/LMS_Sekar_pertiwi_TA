@extends('layouts.app')

@section('judul', 'Forum Diskusi')

@section('konten')
<x-judul-halaman judul="Forum diskusi" deskripsi="Ruang bertanya, berbagi ide, dan berdiskusi bersama kelas." />

<div class="grid gap-5 lg:grid-cols-3">
    <div class="lg:col-span-2">
        @if ($daftar->isEmpty())
            <x-card>
                <x-kosong judul="Belum ada forum" :deskripsi="auth()->user()->adalahGuru() ? 'Forum muncul untuk setiap kelas dan mata pelajaran yang kamu ampu.' : 'Forum muncul setelah kamu ditempatkan di kelas dan guru ditugaskan.'" ikon="forum" />
            </x-card>
        @else
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($daftar as $p)
                    <a href="{{ route('forum.topik.index', $p) }}" class="card group flex items-start gap-4 p-5 transition hover:-translate-y-0.5 hover:shadow-(--shadow-pop)">
                        <x-sampul-mapel :nama="$p->mataPelajaran->nama" class="size-14 shrink-0 rounded-xl" />
                        <div class="min-w-0 flex-1">
                            <h2 class="truncate font-bold text-ink">{{ $p->mataPelajaran->nama }}</h2>
                            <p class="text-xs text-muted">Kelas {{ $p->kelas->nama }} · {{ $p->guru->nama }}</p>
                            <p class="mt-3 text-sm font-semibold text-navy">{{ $p->forum_topik_count }} topik</p>
                            <p class="text-xs text-muted">
                                @if ($p->topik_terakhir)
                                    Terakhir {{ \Illuminate\Support\Carbon::parse($p->topik_terakhir)->diffForHumans() }}
                                @else
                                    Belum ada diskusi
                                @endif
                            </p>
                        </div>
                        <x-ikon nama="kanan" class="mt-1 size-4 shrink-0 text-muted transition group-hover:translate-x-0.5" />
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <div>@include('forum.topik.partials.panduan')</div>
</div>
@endsection
