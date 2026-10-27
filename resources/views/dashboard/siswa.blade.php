@extends('layouts.app')

@section('judul', 'Dashboard Siswa')

@section('konten')
<x-judul-halaman judul="Dashboard Siswa" deskripsi="Ringkasan aktivitas belajar dan progres akademikmu." />

@if (! auth()->user()->kelas_id)
    <x-alert tipe="peringatan" class="mb-5" judul="Kamu belum ditempatkan di kelas">
        Hubungi admin sekolah supaya akunmu ditempatkan di kelas. Setelah itu mata pelajaran, materi, dan tugas akan muncul di sini.
    </x-alert>
@endif

@php
    $rata = $nilaiTerbaru->isEmpty() ? null : round($nilaiTerbaru->avg(fn ($n) => (float) $n->nilai / $n->tugas->nilai_maksimum * 100), 1);
@endphp

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-kartu-stat label="Mata pelajaran" :nilai="str_pad($daftarPenugasan->count(), 2, '0', STR_PAD_LEFT)" :catatan="auth()->user()->kelas ? 'Kelas '.auth()->user()->kelas->nama : 'Belum ada kelas'" warna="navy" :href="route('siswa.mata-pelajaran.index')" />
    <x-kartu-stat label="Tugas aktif" :nilai="str_pad($tugasMendatang->count(), 2, '0', STR_PAD_LEFT)" :catatan="$tugasMendatang->where('tenggat', '<', now()->addDays(2))->count().' segera berakhir'" warna="danger" :href="route('siswa.nilai.index')" />
    <x-kartu-stat label="Rata-rata nilai" :nilai="$rata !== null ? number_format($rata, 1, ',', '.') : '-'" :catatan="$rata !== null ? 'Dari '.$nilaiTerbaru->count().' tugas terbaru' : 'Belum ada tugas dinilai'" warna="gold" />
    <x-kartu-stat label="Terlambat" :nilai="str_pad($tugasLewat->count(), 2, '0', STR_PAD_LEFT)" :catatan="$tugasLewat->isEmpty() ? 'Tidak ada tugas lewat tenggat' : 'Tugas lewat tenggat, belum dikumpulkan'" :warna="$tugasLewat->isEmpty() ? 'success' : 'danger'" />
</div>

<div class="mt-5 grid gap-5 lg:grid-cols-3">
    <x-card judul="Mata pelajaran" deskripsi="Pilih mata pelajaran untuk membuka materi dan tugas." class="lg:col-span-2">
        <x-slot:aksi><a href="{{ route('siswa.mata-pelajaran.index') }}" class="tautan text-sm">Lihat semua →</a></x-slot:aksi>

        @if ($daftarPenugasan->isEmpty())
            <x-kosong judul="Belum ada mata pelajaran" deskripsi="Mata pelajaran muncul setelah kamu ditempatkan di kelas dan guru ditugaskan." ikon="buku" />
        @else
            <ul class="space-y-1">
                @foreach ($daftarPenugasan->take(6) as $p)
                    <li>
                        <a href="{{ route('siswa.mata-pelajaran.show', $p) }}" class="-mx-2 flex items-center gap-4 rounded-xl px-2 py-2.5 transition hover:bg-canvas">
                            <x-sampul-mapel :nama="$p->mataPelajaran->nama" class="size-14 shrink-0 rounded-xl" />
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-semibold text-ink">{{ $p->mataPelajaran->nama }}</span>
                                <span class="block truncate text-xs text-muted">{{ $p->guru->nama }}</span>
                            </span>
                            <span class="hidden shrink-0 text-right text-xs text-muted sm:block">{{ $p->materi_count }} materi<br>{{ $p->tugas_count }} tugas</span>
                            <x-ikon nama="kanan" class="size-4 shrink-0 text-muted" />
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>

    <x-card judul="Tenggat terdekat" :tanpa-padding="true">
        @forelse ($tugasMendatang as $t)
            @php($sisa = now()->diffInHours($t->tenggat))
            <a href="{{ route('siswa.tugas.show', $t) }}" class="flex items-start gap-3 border-t border-line px-5 py-3 first:border-t-0 transition hover:bg-canvas">
                <span class="mt-0.5 shrink-0 rounded-lg px-2 py-1 text-xs font-bold {{ $sisa < 48 ? 'bg-danger-soft text-danger' : 'bg-info-soft text-navy' }}">{{ $t->tenggat->translatedFormat('d M') }}</span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-semibold text-ink">{{ $t->judul }}</span>
                    <span class="block text-xs text-muted">{{ $t->penugasanGuru->mataPelajaran->nama }} · {{ $t->tenggat->translatedFormat('H:i') }}</span>
                </span>
            </a>
        @empty
            <x-kosong judul="Tidak ada tugas menunggu" deskripsi="Semua tugas sudah dikumpulkan. Kerja bagus!" ikon="sukses" />
        @endforelse
    </x-card>
</div>

@if ($tugasLewat->isNotEmpty())
    <x-alert tipe="galat" class="mt-5" judul="Ada tugas yang lewat tenggat dan belum kamu kumpulkan">
        @foreach ($tugasLewat as $t)
            <a href="{{ route('siswa.tugas.show', $t) }}" class="tautan">{{ $t->judul }}</a> ({{ $t->penugasanGuru->mataPelajaran->nama }}){{ $loop->last ? '.' : ',' }}
        @endforeach
        Kamu masih bisa mengumpulkannya, tetapi akan tercatat terlambat.
    </x-alert>
@endif

<div class="mt-5 grid gap-5 lg:grid-cols-3">
    <x-card judul="Materi terbaru" :tanpa-padding="true">
        @forelse ($materiTerbaru as $m)
            <a href="{{ route('siswa.materi.show', $m) }}" class="flex items-start gap-3 border-t border-line px-5 py-3 first:border-t-0 transition hover:bg-canvas">
                <span class="mt-0.5 grid size-9 shrink-0 place-items-center rounded-lg bg-info-soft text-navy"><x-ikon nama="dokumen" class="size-[18px]" /></span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-semibold text-ink">{{ $m->judul }}</span>
                    <span class="block text-xs text-muted">{{ $m->penugasanGuru->mataPelajaran->nama }} · {{ $m->created_at->diffForHumans() }}</span>
                </span>
            </a>
        @empty
            <x-kosong judul="Belum ada materi terbit" ikon="dokumen" />
        @endforelse
    </x-card>

    <x-card judul="Nilai terbaru" :tanpa-padding="true">
        @forelse ($nilaiTerbaru as $n)
            <a href="{{ route('siswa.tugas.show', $n->tugas_id) }}" class="flex items-center gap-3 border-t border-line px-5 py-3 first:border-t-0 transition hover:bg-canvas">
                <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-success-soft text-sm font-bold text-success">{{ rtrim(rtrim(number_format((float) $n->nilai, 2, ',', ''), '0'), ',') }}</span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-semibold text-ink">{{ $n->tugas->judul }}</span>
                    <span class="block text-xs text-muted">{{ $n->tugas->penugasanGuru->mataPelajaran->nama }} · dari {{ $n->tugas->nilai_maksimum }}</span>
                </span>
            </a>
        @empty
            <x-kosong judul="Belum ada nilai" deskripsi="Nilai muncul setelah guru menilai tugasmu." ikon="nilai" />
        @endforelse
    </x-card>

    <x-card judul="Forum terbaru" :tanpa-padding="true">
        @forelse ($topikTerbaru as $t)
            <a href="{{ route('forum.topik.show', $t) }}" class="flex items-start gap-3 border-t border-line px-5 py-3 first:border-t-0 transition hover:bg-canvas">
                <x-avatar :nama="$t->pembuat->nama" ukuran="size-9 text-xs" />
                <span class="min-w-0">
                    <span class="block truncate text-sm font-semibold text-ink">{{ $t->judul }}</span>
                    <span class="block text-xs text-muted">{{ $t->pembuat->nama }} · {{ $t->penugasanGuru->mataPelajaran->nama }} · {{ $t->balasan_count }} balasan</span>
                </span>
            </a>
        @empty
            <x-kosong judul="Belum ada diskusi" ikon="forum" />
        @endforelse
    </x-card>
</div>
@endsection
