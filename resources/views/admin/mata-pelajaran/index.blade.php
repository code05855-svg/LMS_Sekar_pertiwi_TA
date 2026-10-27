@extends('layouts.app')

@section('judul', 'Mata Pelajaran')

@section('konten')
<x-judul-halaman judul="Mata pelajaran" deskripsi="Daftar mata pelajaran yang diajarkan di sekolah.">
    <a href="{{ route('admin.mata-pelajaran.create') }}" class="btn-primary"><x-ikon nama="tambah" class="size-4" /> Tambah mata pelajaran</a>
</x-judul-halaman>

<form method="GET" class="card mb-5 flex flex-wrap items-center gap-3 p-3">
    <div class="relative min-w-52 flex-1">
        <x-ikon nama="cari" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted" />
        <input type="search" name="cari" value="{{ request('cari') }}" placeholder="Cari nama atau kode..." class="input pl-10" aria-label="Cari mata pelajaran">
    </div>
    <button class="btn-secondary" type="submit">Cari</button>
    @if (request()->filled('cari'))<a href="{{ route('admin.mata-pelajaran.index') }}" class="tautan text-sm">Atur ulang</a>@endif
</form>

<x-card :tanpa-padding="true">
    @if ($daftar->isEmpty())
        <x-kosong judul="Belum ada mata pelajaran" :deskripsi="request()->filled('cari') ? 'Tidak ada yang cocok dengan pencarian.' : 'Tambahkan mata pelajaran seperti Matematika, IPA, atau Bahasa Indonesia.'" ikon="buku">
            @unless (request()->filled('cari'))<a href="{{ route('admin.mata-pelajaran.create') }}" class="btn-primary">Tambah mata pelajaran</a>@endunless
        </x-kosong>
    @else
        <x-tabel-data :kolom="['Mata pelajaran', 'Kode', 'Dipakai di', ['label' => 'Aksi', 'kelas' => 'text-right']]" class="px-3 pt-3">
            @foreach ($daftar as $m)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <x-sampul-mapel :nama="$m->nama" class="size-10 shrink-0 rounded-xl" />
                            <span class="font-semibold text-ink">{{ $m->nama }}</span>
                        </div>
                    </td>
                    <td><span class="badge-info">{{ $m->kode }}</span></td>
                    <td class="text-muted">{{ $m->penugasan_guru_count }} kelas</td>
                    <td>
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('admin.mata-pelajaran.edit', $m) }}" class="btn-icon" title="Ubah" aria-label="Ubah {{ $m->nama }}"><x-ikon nama="ubah" class="size-[18px]" /></a>
                            <form method="POST" action="{{ route('admin.mata-pelajaran.destroy', $m) }}" data-konfirmasi="{{ $m->nama }} akan dihapus. Mata pelajaran yang sudah dipakai pada penugasan guru tidak bisa dihapus." data-judul="Hapus mata pelajaran?" data-tombol="Hapus">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon hover:!bg-danger-soft hover:!text-danger" title="Hapus" aria-label="Hapus {{ $m->nama }}"><x-ikon nama="hapus" class="size-[18px]" /></button>
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
