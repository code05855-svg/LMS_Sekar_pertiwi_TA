@extends('layouts.app')

@section('judul', 'Pengumpulan — '.$tugas->judul)

@section('konten')
@php
    $pg = $tugas->penugasanGuru;
    $jumlahBelumDinilai = $pengumpulan->filter(fn ($p) => ! $p->telahDinilai())->count();
    $jumlahBelumKumpul = $siswa->count() - $pengumpulan->count();
@endphp

<x-judul-halaman :judul="'Pengumpulan: '.$tugas->judul" :deskripsi="$pg->mataPelajaran->nama.' · Kelas '.$pg->kelas->nama.' · tenggat '.$tugas->tenggat->translatedFormat('d M Y, H:i')" :kembali="route('guru.tugas.show', $tugas)" label="Detail tugas" />

<div x-data="{ filter: 'semua' }">
    <div class="mb-4 flex flex-wrap gap-2" role="tablist" aria-label="Filter pengumpulan">
        @foreach ([
            'semua' => ['Semua', $siswa->count()],
            'perlu' => ['Perlu dinilai', $jumlahBelumDinilai],
            'dinilai' => ['Sudah dinilai', $pengumpulan->count() - $jumlahBelumDinilai],
            'belum' => ['Belum mengumpulkan', $jumlahBelumKumpul],
        ] as $kunci => [$nama, $jumlah])
            <button type="button" role="tab" @click="filter = '{{ $kunci }}'" :aria-selected="filter === '{{ $kunci }}'"
                    :class="filter === '{{ $kunci }}' ? 'bg-navy text-white' : 'bg-white text-ink ring-1 ring-inset ring-line hover:bg-canvas'"
                    class="rounded-full px-4 py-2 text-sm font-semibold transition">
                {{ $nama }} <span class="ml-1 opacity-70">{{ $jumlah }}</span>
            </button>
        @endforeach
    </div>

    <x-card :tanpa-padding="true">
        @if ($siswa->isEmpty())
            <x-kosong judul="Belum ada siswa di kelas ini" deskripsi="Admin sekolah menempatkan siswa ke kelas lewat menu Penempatan Siswa." ikon="pengguna" />
        @else
            <x-tabel-data :kolom="['Siswa', 'Berkas', 'Dikumpulkan', 'Nilai', ['label' => 'Aksi', 'kelas' => 'text-right']]" class="px-3 py-3">
                @foreach ($siswa as $s)
                    @php
                        $p = $pengumpulan->get($s->id);
                        $kategori = ! $p ? 'belum' : ($p->telahDinilai() ? 'dinilai' : 'perlu');
                    @endphp
                    <tr x-show="filter === 'semua' || filter === '{{ $kategori }}'">
                        <td>
                            <div class="flex items-center gap-3">
                                <x-avatar :nama="$s->nama" ukuran="size-9 text-xs" />
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-ink">{{ $s->nama }}</p>
                                    <p class="text-xs text-muted">{{ $s->nomor_induk }}</p>
                                </div>
                            </div>
                        </td>
                        @if ($p)
                            <td class="max-w-48">
                                <a href="{{ route('guru.pengumpulan-tugas.unduh', [$p, 'lihat' => 1]) }}" target="_blank" rel="noopener" class="tautan inline-flex max-w-full items-center gap-1.5 text-sm"><x-ikon nama="klip" class="size-4 shrink-0" /><span class="truncate">{{ $p->nama_asli }}</span></a>
                                <span class="block text-xs text-muted">{{ \App\Support\Format::ukuran($p->ukuran) }}</span>
                            </td>
                            <td class="whitespace-nowrap">
                                <span class="text-ink">{{ $p->waktu_kumpul->translatedFormat('d M, H:i') }}</span>
                                <x-badge-status :status="$p->status" class="ml-1" />
                            </td>
                            <td class="whitespace-nowrap">
                                @if ($p->telahDinilai())
                                    <span class="text-lg font-bold text-success">{{ \App\Support\Format::nilai($p->nilai) }}</span><span class="text-xs text-muted"> / {{ $tugas->nilai_maksimum }}</span>
                                @else
                                    <span class="badge-gold">Belum dinilai</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('guru.pengumpulan-tugas.show', $p) }}" class="{{ $p->telahDinilai() ? 'btn-secondary' : 'btn-primary' }} btn-sm">{{ $p->telahDinilai() ? 'Ubah nilai' : 'Nilai' }}</a>
                            </td>
                        @else
                            <td colspan="3" class="text-muted">
                                <x-badge-status status="belum" />
                                @if ($tugas->tenggat->isPast())<span class="ml-2 text-xs text-danger">Lewat tenggat</span>@endif
                            </td>
                            <td class="text-right text-xs text-muted">—</td>
                        @endif
                    </tr>
                @endforeach
            </x-tabel-data>
        @endif
    </x-card>
</div>
@endsection
