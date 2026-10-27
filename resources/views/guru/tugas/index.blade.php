@extends('layouts.app')

@section('judul', 'Tugas')

@section('konten')
<x-judul-halaman judul="Tugas" deskripsi="Semua tugas yang kamu berikan, terbaru di atas.">
    <a href="{{ route('guru.tugas.create') }}" class="btn-primary"><x-ikon nama="tambah" class="size-4" /> Buat tugas</a>
</x-judul-halaman>

<form method="GET" class="card mb-5 flex flex-wrap items-center gap-3 p-3">
    <div class="relative min-w-52 flex-1">
        <x-ikon nama="cari" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted" />
        <input type="search" name="cari" value="{{ request('cari') }}" placeholder="Cari judul tugas..." class="input pl-10" aria-label="Cari tugas">
    </div>
    <select name="penugasan" class="input w-auto" aria-label="Kelas dan mata pelajaran" onchange="this.form.submit()">
        <option value="">Semua kelas</option>
        @foreach ($daftarPenugasan as $p)
            <option value="{{ $p->id }}" @selected((string) request('penugasan') === (string) $p->id)>{{ $p->mataPelajaran->nama }} — {{ $p->kelas->nama }}</option>
        @endforeach
    </select>
    <button class="btn-secondary" type="submit">Cari</button>
    @if (request()->hasAny(['cari', 'penugasan']))<a href="{{ route('guru.tugas.index') }}" class="tautan text-sm">Atur ulang</a>@endif
</form>

<x-card :tanpa-padding="true">
    @if ($daftar->isEmpty())
        <x-kosong judul="Belum ada tugas" :deskripsi="request()->hasAny(['cari', 'penugasan']) ? 'Tidak ada tugas yang cocok dengan filter.' : 'Buat tugas pertama untuk salah satu kelasmu.'" ikon="tugas">
            <a href="{{ route('guru.tugas.create') }}" class="btn-primary btn-sm">Buat tugas</a>
        </x-kosong>
    @else
        <x-tabel-data :kolom="['Tugas', 'Kelas', 'Tenggat', 'Terkumpul', ['label' => 'Aksi', 'kelas' => 'text-right']]" class="px-3 pt-3">
            @foreach ($daftar as $t)
                <tr>
                    <td class="max-w-xs"><a href="{{ route('guru.tugas.show', $t) }}" class="block truncate font-semibold text-ink hover:text-navy">{{ $t->judul }}</a></td>
                    <td class="whitespace-nowrap">
                        <span class="font-medium text-ink">{{ $t->penugasanGuru->mataPelajaran->nama }}</span>
                        <span class="block text-xs text-muted">Kelas {{ $t->penugasanGuru->kelas->nama }}</span>
                    </td>
                    <td class="whitespace-nowrap">
                        <span class="{{ $t->tenggat->isPast() ? 'text-muted' : 'font-medium text-ink' }}">{{ $t->tenggat->translatedFormat('d M Y, H:i') }}</span>
                        @if ($t->tenggat->isPast())<span class="block text-xs text-muted">Sudah lewat</span>@endif
                    </td>
                    <td class="whitespace-nowrap">
                        <span class="font-semibold text-ink">{{ $t->pengumpulan_tugas_count }}</span>
                        @if ($t->belum_dinilai_count)<span class="badge-danger ml-1">{{ $t->belum_dinilai_count }} perlu dinilai</span>@endif
                    </td>
                    <td>
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('guru.pengumpulan-tugas.index', $t) }}" class="btn-secondary btn-sm">Pengumpulan</a>
                            <a href="{{ route('guru.tugas.edit', $t) }}" class="btn-icon" title="Ubah" aria-label="Ubah {{ $t->judul }}"><x-ikon nama="ubah" class="size-[18px]" /></a>
                            <form method="POST" action="{{ route('guru.tugas.destroy', $t) }}" data-konfirmasi="Tugas “{{ $t->judul }}” beserta {{ $t->pengumpulan_tugas_count }} jawaban dan nilainya akan dihapus permanen." data-judul="Hapus tugas?" data-tombol="Hapus">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon hover:!bg-danger-soft hover:!text-danger" title="Hapus" aria-label="Hapus {{ $t->judul }}"><x-ikon nama="hapus" class="size-[18px]" /></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-tabel-data>
        <div class="px-5 pb-5">{{ $daftar->links() }}</div>
    @endif
</x-card>
@endsection
