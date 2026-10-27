@extends('layouts.app')

@section('judul', 'Dashboard Guru')

@section('konten')
<x-judul-halaman judul="Dashboard Guru" deskripsi="Kelola kelas, materi, tugas, dan penilaian siswa." />

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-kartu-stat label="Kelas diampu" :nilai="$statistik['kelas']" catatan="Mata pelajaran dan kelas" warna="navy" :href="route('guru.kelas.index')" />
    <x-kartu-stat label="Siswa aktif" :nilai="$statistik['siswa']" catatan="Di semua kelas yang diampu" warna="success" />
    <x-kartu-stat label="Perlu dinilai" :nilai="$jumlahPerluDinilai" catatan="Pengumpulan masuk" warna="danger" :href="route('guru.tugas.index')" />
    <x-kartu-stat label="Materi terbit" :nilai="$statistik['materi_terbit']" catatan="Semua mata pelajaran" warna="gold" :href="route('guru.materi.index', ['status' => 'terbit'])" />
</div>

@if ($daftarPenugasan->isEmpty())
    <x-alert tipe="peringatan" class="mt-5" judul="Belum ada kelas yang diampu">
        Admin sekolah perlu menambahkan penugasan mengajar untuk akun Anda sebelum Anda bisa membuat materi dan tugas.
    </x-alert>
@else
    <section class="mt-5 flex flex-wrap items-center justify-between gap-4 rounded-2xl bg-navy p-6 text-white">
        <div>
            <h2 class="text-lg font-bold">Mulai dari sini</h2>
            <p class="mt-1 text-sm text-white/80">Buat materi, bagikan tugas, atau periksa pengumpulan terbaru.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('guru.materi.create') }}" class="btn-gold"><x-ikon nama="tambah" class="size-4" /> Buat materi</a>
            <a href="{{ route('guru.tugas.create') }}" class="btn bg-white text-navy hover:bg-info-soft"><x-ikon nama="tambah" class="size-4" /> Buat tugas</a>
        </div>
    </section>
@endif

@if ($berkasGagal->isNotEmpty())
    <x-alert tipe="galat" class="mt-5" judul="{{ $berkasGagal->count() }} berkas gagal diindeks untuk chatbot">
        Chatbot belum bisa menjawab dari berkas ini:
        @foreach ($berkasGagal->take(3) as $b)
            <a href="{{ route('guru.materi.show', $b->materi_id) }}" class="tautan">{{ $b->nama_asli }}</a>{{ $loop->last ? '.' : ',' }}
        @endforeach
        PDF hasil scan atau foto tidak berisi teks yang bisa dibaca.
    </x-alert>
@endif

<div class="mt-5 grid gap-5 lg:grid-cols-5">
    <x-card judul="Perlu dinilai" deskripsi="Jawaban siswa yang menunggu nilai, paling lama lebih dulu." class="lg:col-span-3" :tanpa-padding="true">
        @if ($perluDinilai->isEmpty())
            <x-kosong judul="Semua pengumpulan sudah dinilai" deskripsi="Jawaban baru dari siswa akan muncul di sini." ikon="sukses" />
        @else
            <x-tabel-data :kolom="['Siswa', 'Tugas', 'Dikumpulkan', '']" class="px-3 pb-3">
                @foreach ($perluDinilai as $p)
                    <tr>
                        <td>
                            <p class="font-semibold text-ink">{{ $p->siswa->nama }}</p>
                            <p class="text-xs text-muted">{{ $p->tugas->penugasanGuru->kelas->nama }}</p>
                        </td>
                        <td class="max-w-48 truncate text-muted">{{ $p->tugas->judul }}</td>
                        <td class="whitespace-nowrap">
                            <span class="text-muted">{{ $p->waktu_kumpul->diffForHumans() }}</span>
                            @if ($p->status === \App\Enums\StatusPengumpulan::Terlambat)<x-badge-status :status="$p->status" class="ml-1" />@endif
                        </td>
                        <td class="text-right"><a href="{{ route('guru.pengumpulan-tugas.show', $p) }}" class="tautan text-sm">Nilai →</a></td>
                    </tr>
                @endforeach
            </x-tabel-data>
        @endif
    </x-card>

    <x-card judul="Kelas saya" class="lg:col-span-2" :tanpa-padding="true">
        <x-slot:aksi><a href="{{ route('guru.kelas.index') }}" class="tautan text-sm">Semua →</a></x-slot:aksi>
        @if ($daftarPenugasan->isEmpty())
            <x-kosong judul="Belum ada kelas" ikon="kelas" />
        @else
            <ul class="divide-y divide-line px-5 pb-3">
                @foreach ($daftarPenugasan->take(6) as $p)
                    <li>
                        <a href="{{ route('guru.kelas.show', $p) }}" class="-mx-2 flex items-center gap-3 rounded-xl px-2 py-3 transition hover:bg-canvas">
                            <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-info-soft text-sm font-bold text-navy">{{ $p->kelas->nama }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-semibold text-ink">{{ $p->mataPelajaran->nama }}</span>
                                <span class="block text-xs text-muted">{{ $p->kelas->siswa_count }} siswa · {{ $p->materi_count }} materi · {{ $p->tugas_count }} tugas</span>
                            </span>
                            <x-ikon nama="kanan" class="size-4 text-muted" />
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>
</div>

<div class="mt-5 grid gap-5 lg:grid-cols-3">
    <x-card judul="Tenggat terdekat" :tanpa-padding="true">
        @forelse ($tenggatTerdekat as $t)
            <a href="{{ route('guru.tugas.show', $t) }}" class="flex items-start gap-3 border-t border-line px-5 py-3 first:border-t-0 transition hover:bg-canvas">
                <span class="mt-0.5 grid size-9 shrink-0 place-items-center rounded-lg bg-gold-soft text-[#8a6500]"><x-ikon nama="jam" class="size-[18px]" /></span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-semibold text-ink">{{ $t->judul }}</span>
                    <span class="block text-xs text-muted">{{ $t->penugasanGuru->mataPelajaran->nama }} · {{ $t->penugasanGuru->kelas->nama }}</span>
                    <span class="block text-xs font-medium text-danger">{{ $t->tenggat->translatedFormat('d M, H:i') }} · {{ $t->pengumpulan_tugas_count }} terkumpul</span>
                </span>
            </a>
        @empty
            <x-kosong judul="Tidak ada tenggat dekat" ikon="kalender" />
        @endforelse
    </x-card>

    <x-card judul="Materi terbaru" :tanpa-padding="true">
        @forelse ($materiTerbaru as $m)
            <a href="{{ route('guru.materi.show', $m) }}" class="flex items-start gap-3 border-t border-line px-5 py-3 first:border-t-0 transition hover:bg-canvas">
                <span class="mt-0.5 grid size-9 shrink-0 place-items-center rounded-lg bg-info-soft text-navy"><x-ikon nama="dokumen" class="size-[18px]" /></span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-semibold text-ink">{{ $m->judul }}</span>
                    <span class="block text-xs text-muted">{{ $m->penugasanGuru->mataPelajaran->nama }} · {{ $m->penugasanGuru->kelas->nama }} · {{ $m->berkas_materi_count }} berkas</span>
                </span>
                <x-badge-status :status="$m->status" />
            </a>
        @empty
            <x-kosong judul="Belum ada materi" ikon="dokumen"><a href="{{ route('guru.materi.create') }}" class="btn-primary btn-sm">Buat materi</a></x-kosong>
        @endforelse
    </x-card>

    <x-card judul="Forum terbaru" :tanpa-padding="true">
        @forelse ($topikTerbaru as $t)
            <a href="{{ route('forum.topik.show', $t) }}" class="flex items-start gap-3 border-t border-line px-5 py-3 first:border-t-0 transition hover:bg-canvas">
                <x-avatar :nama="$t->pembuat->nama" ukuran="size-9 text-xs" />
                <span class="min-w-0">
                    <span class="block truncate text-sm font-semibold text-ink">{{ $t->judul }}</span>
                    <span class="block text-xs text-muted">{{ $t->pembuat->nama }} · {{ $t->penugasanGuru->mataPelajaran->nama }} {{ $t->penugasanGuru->kelas->nama }} · {{ $t->balasan_count }} balasan</span>
                </span>
            </a>
        @empty
            <x-kosong judul="Belum ada diskusi" ikon="forum" />
        @endforelse
    </x-card>
</div>
@endsection
