@extends('layouts.app')

@section('judul', $penugasan->mataPelajaran->nama)

@section('konten')
<x-judul-halaman :judul="$penugasan->mataPelajaran->nama" :deskripsi="'Kelas '.$penugasan->kelas->nama.' · '.$penugasan->guru->nama" :kembali="route('siswa.mata-pelajaran.index')" label="Mata pelajaran" />

<div class="grid gap-5 lg:grid-cols-3">
    <div class="space-y-5 lg:col-span-2">
        <section aria-labelledby="judul-materi">
            <h2 id="judul-materi" class="mb-3 text-lg font-bold tracking-tight text-ink">Materi pembelajaran</h2>
            @if ($daftarMateri->isEmpty())
                <x-card><x-kosong judul="Belum ada materi" deskripsi="Guru belum menerbitkan materi untuk mata pelajaran ini." ikon="dokumen" /></x-card>
            @else
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($daftarMateri as $m)
                        <a href="{{ route('siswa.materi.show', $m) }}" class="card group flex flex-col overflow-hidden transition hover:-translate-y-0.5 hover:shadow-(--shadow-pop)">
                            <x-sampul-mapel :nama="$penugasan->mataPelajaran->nama" class="h-24">
                                <span class="absolute bottom-2 right-3 rounded-full bg-white/95 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-navy">{{ $penugasan->mataPelajaran->nama }}</span>
                            </x-sampul-mapel>
                            <div class="flex flex-1 flex-col p-4">
                                <h3 class="font-bold text-ink">{{ $m->judul }}</h3>
                                <p class="mt-1 line-clamp-2 text-sm text-muted">{{ $m->deskripsi ?: 'Materi PDF dari gurumu.' }}</p>
                                <p class="mt-3 text-xs text-muted">{{ $m->berkas_materi_count }} berkas · PDF · {{ $m->created_at->translatedFormat('d M Y') }}</p>
                                <span class="mt-3 inline-flex items-center gap-1.5 text-sm font-bold text-navy">Buka materi <x-ikon nama="panah" class="size-4 transition group-hover:translate-x-0.5" /></span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        <x-card judul="Tugas" :tanpa-padding="true">
            @if ($daftarTugas->isEmpty())
                <x-kosong judul="Belum ada tugas" deskripsi="Tugas dari gurumu akan muncul di sini." ikon="tugas" />
            @else
                <x-tabel-data :kolom="['Tugas', 'Tenggat', 'Status', '']" class="px-3 pb-3">
                    @foreach ($daftarTugas as $t)
                        @php($p = $t->pengumpulanTugas->first())
                        <tr>
                            <td class="max-w-xs"><a href="{{ route('siswa.tugas.show', $t) }}" class="block truncate font-semibold text-ink hover:text-navy">{{ $t->judul }}</a></td>
                            <td class="whitespace-nowrap {{ ! $p && $t->tenggat->isPast() ? 'text-danger' : 'text-muted' }}">{{ $t->tenggat->translatedFormat('d M Y, H:i') }}</td>
                            <td class="whitespace-nowrap">
                                @include('siswa.partials.status-tugas', ['tugas' => $t, 'pengumpulan' => $p])
                                @if ($p?->telahDinilai())<span class="ml-1 text-sm font-bold text-success">{{ \App\Support\Format::nilai($p->nilai) }}</span>@endif
                            </td>
                            <td class="text-right"><a href="{{ route('siswa.tugas.show', $t) }}" class="tautan text-sm">Detail →</a></td>
                        </tr>
                    @endforeach
                </x-tabel-data>
            @endif
        </x-card>
    </div>

    <div class="space-y-5">
        <x-card judul="Guru pengampu">
            <div class="flex items-center gap-3">
                <x-avatar :nama="$penugasan->guru->nama" />
                <div class="min-w-0">
                    <p class="truncate font-semibold text-ink">{{ $penugasan->guru->nama }}</p>
                    <p class="text-xs text-muted">{{ $penugasan->mataPelajaran->nama }} · Kelas {{ $penugasan->kelas->nama }}</p>
                </div>
            </div>
        </x-card>

        <x-card judul="Forum diskusi">
            <p class="text-sm text-muted">{{ $jumlahTopik ? $jumlahTopik.' topik diskusi di mata pelajaran ini.' : 'Belum ada diskusi. Jadilah yang pertama bertanya.' }}</p>
            <a href="{{ route('forum.topik.index', $penugasan) }}" class="btn-secondary btn-sm mt-4"><x-ikon nama="forum" class="size-4" /> Buka forum</a>
        </x-card>

        <section class="rounded-2xl bg-navy p-5 text-white">
            <div class="flex items-center gap-3">
                <span class="grid size-10 place-items-center rounded-full bg-gold text-navy"><x-ikon nama="robot" class="size-6" /></span>
                <p class="font-bold">Bingung dengan materinya?</p>
            </div>
            <p class="mt-2 text-sm text-white/80">Tanya Sekar AI. Jawabannya diambil dari PDF materi gurumu, lengkap dengan sumbernya.</p>
            <a href="{{ route('chatbot.index') }}" class="btn-gold btn-sm mt-4">Tanya Sekar AI</a>
        </section>
    </div>
</div>
@endsection
