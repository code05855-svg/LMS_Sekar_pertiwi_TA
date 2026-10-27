@extends('layouts.app')

@section('judul', 'Tugas & Penilaian')

@section('konten')
<x-judul-halaman judul="Tugas & penilaian" deskripsi="Pantau tugas, tenggat, pengumpulan, dan umpan balik dari guru." />

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-kartu-stat label="Total tugas" :nilai="$ringkasan['tugas']" catatan="Di semua mata pelajaran" warna="navy" />
    <x-kartu-stat label="Dikumpulkan" :nilai="$ringkasan['dikumpulkan']" :catatan="($ringkasan['tugas'] - $ringkasan['dikumpulkan']).' belum dikumpulkan'" warna="gold" />
    <x-kartu-stat label="Sudah dinilai" :nilai="$ringkasan['dinilai']" catatan="Nilai dari guru" warna="success" />
    <x-kartu-stat label="Rata-rata nilai" :nilai="$ringkasan['rata_rata'] !== null ? number_format($ringkasan['rata_rata'], 1, ',', '.') : '-'" catatan="Skala 0–100" warna="navy" />
</div>

<div x-data="{ filter: 'semua' }" class="mt-5">
    <div class="mb-4 flex flex-wrap gap-2" role="tablist" aria-label="Filter tugas">
        @foreach (['semua' => 'Semua tugas', 'belum' => 'Belum dikerjakan', 'kumpul' => 'Sudah dikumpulkan'] as $kunci => $nama)
            <button type="button" role="tab" @click="filter = '{{ $kunci }}'" :aria-selected="filter === '{{ $kunci }}'"
                    :class="filter === '{{ $kunci }}' ? 'bg-navy text-white' : 'bg-white text-ink ring-1 ring-inset ring-line hover:bg-canvas'"
                    class="rounded-full px-4 py-2 text-sm font-semibold transition">{{ $nama }}</button>
        @endforeach
    </div>

    <x-card judul="Daftar tugas" :tanpa-padding="true">
        @if ($daftar->isEmpty())
            <x-kosong judul="Belum ada tugas" deskripsi="Tugas dari guru kelasmu akan muncul di sini." ikon="tugas" />
        @else
            <x-tabel-data :kolom="['Tugas', 'Mata pelajaran', 'Tenggat', 'Status', 'Nilai', '']" class="px-3 pb-3">
                @foreach ($daftar as $t)
                    @php
                        $p = $t->pengumpulanTugas->first();
                        $kategori = $p ? 'kumpul' : 'belum';
                    @endphp
                    <tr x-show="filter === 'semua' || filter === '{{ $kategori }}'">
                        <td class="max-w-xs"><a href="{{ route('siswa.tugas.show', $t) }}" class="block truncate font-semibold text-ink hover:text-navy">{{ $t->judul }}</a></td>
                        <td class="whitespace-nowrap text-muted">{{ $t->penugasanGuru->mataPelajaran->nama }}</td>
                        <td class="whitespace-nowrap {{ ! $p && $t->tenggat->isPast() ? 'font-semibold text-danger' : 'text-muted' }}">{{ $t->tenggat->translatedFormat('d M Y') }}</td>
                        <td class="whitespace-nowrap">@include('siswa.partials.status-tugas', ['tugas' => $t, 'pengumpulan' => $p])</td>
                        <td class="whitespace-nowrap">
                            @if ($p?->telahDinilai())
                                <span class="font-bold text-success">{{ \App\Support\Format::nilai($p->nilai) }}</span><span class="text-xs text-muted"> / {{ $t->nilai_maksimum }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-right"><a href="{{ route('siswa.tugas.show', $t) }}" class="tautan text-sm">Detail →</a></td>
                    </tr>
                @endforeach
            </x-tabel-data>
        @endif
    </x-card>
</div>
@endsection
