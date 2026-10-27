@extends('layouts.app')

@section('judul', 'Kelas Saya')

@section('konten')
<x-judul-halaman judul="Kelas saya" deskripsi="Mata pelajaran dan kelas yang kamu ampu. Pilih salah satu untuk mengelola isinya." />

@if ($daftar->isEmpty())
    <x-card>
        <x-kosong judul="Belum ada kelas yang diampu" deskripsi="Admin sekolah perlu menambahkan penugasan mengajar untuk akunmu. Setelah itu kelas muncul di sini." ikon="kelas" />
    </x-card>
@else
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($daftar as $p)
            <article class="card flex flex-col overflow-hidden">
                <x-sampul-mapel :nama="$p->mataPelajaran->nama" class="h-32">
                    <span class="absolute bottom-3 left-4 rounded-full bg-white/95 px-3 py-1 text-xs font-bold text-navy">Kelas {{ $p->kelas->nama }}</span>
                </x-sampul-mapel>
                <div class="flex flex-1 flex-col p-5">
                    <h2 class="text-lg font-bold tracking-tight text-ink">{{ $p->mataPelajaran->nama }}</h2>
                    <p class="text-xs text-muted">{{ $p->kelas->tahun_ajaran }} · {{ $p->kelas->siswa_count }} siswa</p>

                    <dl class="mt-4 grid grid-cols-3 gap-2 text-center">
                        <div class="rounded-xl bg-canvas py-2"><dd class="text-lg font-bold text-ink">{{ $p->materi_count }}</dd><dt class="text-[11px] text-muted">Materi</dt></div>
                        <div class="rounded-xl bg-canvas py-2"><dd class="text-lg font-bold text-ink">{{ $p->tugas_count }}</dd><dt class="text-[11px] text-muted">Tugas</dt></div>
                        <div class="rounded-xl bg-canvas py-2"><dd class="text-lg font-bold text-ink">{{ $p->forum_topik_count }}</dd><dt class="text-[11px] text-muted">Diskusi</dt></div>
                    </dl>

                    <div class="mt-5 flex flex-wrap gap-2 border-t border-line pt-4">
                        <a href="{{ route('guru.kelas.show', $p) }}" class="btn-primary btn-sm">Buka kelas <x-ikon nama="panah" class="size-4" /></a>
                        <a href="{{ route('forum.topik.index', $p) }}" class="btn-ghost btn-sm">Forum</a>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
@endif
@endsection
