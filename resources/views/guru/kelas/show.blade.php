@extends('layouts.app')

@section('judul', $penugasan->mataPelajaran->nama.' '.$penugasan->kelas->nama)

@section('konten')
<x-judul-halaman :judul="$penugasan->mataPelajaran->nama.' · Kelas '.$penugasan->kelas->nama" :deskripsi="'Tahun ajaran '.$penugasan->kelas->tahun_ajaran.' · '.$daftarSiswa->count().' siswa'" :kembali="route('guru.kelas.index')" label="Kelas saya">
    <a href="{{ route('guru.materi.create', ['penugasan' => $penugasan->id]) }}" class="btn-primary"><x-ikon nama="tambah" class="size-4" /> Materi</a>
    <a href="{{ route('guru.tugas.create', ['penugasan' => $penugasan->id]) }}" class="btn-gold"><x-ikon nama="tambah" class="size-4" /> Tugas</a>
</x-judul-halaman>

<div class="grid gap-5 lg:grid-cols-3">
    <div class="space-y-5 lg:col-span-2">
        <x-card judul="Materi" :tanpa-padding="true">
            <x-slot:aksi><a href="{{ route('guru.materi.index', ['penugasan' => $penugasan->id]) }}" class="tautan text-sm">Kelola →</a></x-slot:aksi>
            @forelse ($daftarMateri as $m)
                <a href="{{ route('guru.materi.show', $m) }}" class="flex items-center gap-3 border-t border-line px-5 py-3 first:border-t-0 transition hover:bg-canvas">
                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-info-soft text-navy"><x-ikon nama="dokumen" class="size-5" /></span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate font-semibold text-ink">{{ $m->judul }}</span>
                        <span class="block text-xs text-muted">{{ $m->berkas_materi_count }} berkas · {{ $m->berkas_terindeks_count }} terindeks chatbot · {{ $m->created_at->translatedFormat('d M Y') }}</span>
                    </span>
                    <x-badge-status :status="$m->status" />
                </a>
            @empty
                <x-kosong judul="Belum ada materi" deskripsi="Unggah PDF materi supaya siswa bisa membaca dan chatbot bisa menjawab darinya." ikon="dokumen">
                    <a href="{{ route('guru.materi.create', ['penugasan' => $penugasan->id]) }}" class="btn-primary btn-sm">Buat materi</a>
                </x-kosong>
            @endforelse
        </x-card>

        <x-card judul="Tugas" :tanpa-padding="true">
            <x-slot:aksi><a href="{{ route('guru.tugas.index', ['penugasan' => $penugasan->id]) }}" class="tautan text-sm">Kelola →</a></x-slot:aksi>
            @forelse ($daftarTugas as $t)
                <a href="{{ route('guru.tugas.show', $t) }}" class="flex items-center gap-3 border-t border-line px-5 py-3 first:border-t-0 transition hover:bg-canvas">
                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-gold-soft text-[#8a6500]"><x-ikon nama="tugas" class="size-5" /></span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate font-semibold text-ink">{{ $t->judul }}</span>
                        <span class="block text-xs text-muted">Tenggat {{ $t->tenggat->translatedFormat('d M Y, H:i') }} · {{ $t->pengumpulan_tugas_count }}/{{ $daftarSiswa->count() }} terkumpul</span>
                    </span>
                    @if ($t->belum_dinilai_count)
                        <span class="badge-danger">{{ $t->belum_dinilai_count }} perlu dinilai</span>
                    @elseif ($t->tenggat->isPast())
                        <span class="badge-muted">Selesai</span>
                    @else
                        <span class="badge-info">Berjalan</span>
                    @endif
                </a>
            @empty
                <x-kosong judul="Belum ada tugas" ikon="tugas">
                    <a href="{{ route('guru.tugas.create', ['penugasan' => $penugasan->id]) }}" class="btn-primary btn-sm">Buat tugas</a>
                </x-kosong>
            @endforelse
        </x-card>
    </div>

    <div class="space-y-5">
        <x-card judul="Forum kelas" :tanpa-padding="true">
            <x-slot:aksi><a href="{{ route('forum.topik.index', $penugasan) }}" class="tautan text-sm">Buka →</a></x-slot:aksi>
            @forelse ($topikTerbaru as $t)
                <a href="{{ route('forum.topik.show', $t) }}" class="flex items-start gap-3 border-t border-line px-5 py-3 first:border-t-0 transition hover:bg-canvas">
                    <x-avatar :nama="$t->pembuat->nama" ukuran="size-9 text-xs" />
                    <span class="min-w-0">
                        <span class="flex items-center gap-1.5 text-sm font-semibold text-ink">
                            @if ($t->disematkan)<x-ikon nama="sematan" class="size-3.5 shrink-0 text-gold-600" />@endif
                            <span class="truncate">{{ $t->judul }}</span>
                        </span>
                        <span class="block text-xs text-muted">{{ $t->pembuat->nama }} · {{ $t->balasan_count }} balasan</span>
                    </span>
                </a>
            @empty
                <x-kosong judul="Belum ada diskusi" ikon="forum" />
            @endforelse
        </x-card>

        <x-card judul="Siswa ({{ $daftarSiswa->count() }})" :tanpa-padding="true">
            @if ($daftarSiswa->isEmpty())
                <x-kosong judul="Belum ada siswa" deskripsi="Admin menempatkan siswa ke kelas ini." ikon="pengguna" />
            @else
                <ul class="scroll-tipis max-h-72 divide-y divide-line overflow-y-auto px-5 pb-3">
                    @foreach ($daftarSiswa as $s)
                        <li class="flex items-center gap-3 py-2.5">
                            <x-avatar :nama="$s->nama" ukuran="size-8 text-[11px]" />
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium text-ink">{{ $s->nama }}</span>
                                <span class="block text-xs text-muted">{{ $s->nomor_induk }}</span>
                            </span>
                            @unless ($s->aktif)<x-badge-status status="nonaktif" />@endunless
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>
</div>
@endsection
