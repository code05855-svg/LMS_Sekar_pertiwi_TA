@extends('layouts.app')

@section('judul', 'Penugasan Guru')

@section('konten')
<x-judul-halaman judul="Penugasan guru" deskripsi="Guru mana yang mengampu mata pelajaran apa di kelas mana.">
    <a href="{{ route('admin.penugasan-guru.create') }}" class="btn-primary"><x-ikon nama="tambah" class="size-4" /> Tambah penugasan</a>
</x-judul-halaman>

<form method="GET" class="card mb-5 flex flex-wrap items-center gap-3 p-3">
    <select name="kelas" class="input w-auto" aria-label="Filter kelas" onchange="this.form.submit()">
        <option value="">Semua kelas</option>
        @foreach ($daftarKelas as $k)
            <option value="{{ $k->id }}" @selected((string) request('kelas') === (string) $k->id)>{{ $k->tingkat->value }} {{ $k->nama }} · {{ $k->tahun_ajaran }}</option>
        @endforeach
    </select>
    <select name="guru" class="input w-auto" aria-label="Filter guru" onchange="this.form.submit()">
        <option value="">Semua guru</option>
        @foreach ($daftarGuru as $g)
            <option value="{{ $g->id }}" @selected((string) request('guru') === (string) $g->id)>{{ $g->nama }}</option>
        @endforeach
    </select>
    @if (request()->hasAny(['kelas', 'guru']))<a href="{{ route('admin.penugasan-guru.index') }}" class="tautan text-sm">Atur ulang</a>@endif
</form>

<x-card :tanpa-padding="true">
    @if ($daftar->isEmpty())
        <x-kosong judul="Belum ada penugasan" deskripsi="Buat penugasan agar guru bisa mengunggah materi dan tugas untuk kelasnya." ikon="tugas">
            <a href="{{ route('admin.penugasan-guru.create') }}" class="btn-primary">Tambah penugasan</a>
        </x-kosong>
    @else
        <x-tabel-data :kolom="['Kelas', 'Mata pelajaran', 'Guru pengampu', ['label' => 'Aksi', 'kelas' => 'text-right']]" class="px-3 pt-3">
            @foreach ($daftar as $p)
                <tr>
                    <td class="font-semibold text-ink">{{ $p->kelas->tingkat->value }} {{ $p->kelas->nama }} <span class="text-xs font-normal text-muted">· {{ $p->kelas->tahun_ajaran }}</span></td>
                    <td>{{ $p->mataPelajaran->nama }}</td>
                    <td>
                        <div class="flex items-center gap-2">
                            <x-avatar :nama="$p->guru->nama" ukuran="size-8 text-[11px]" />
                            <span>{{ $p->guru->nama }}</span>
                        </div>
                    </td>
                    <td>
                        <div class="flex justify-end">
                            <form method="POST" action="{{ route('admin.penugasan-guru.destroy', $p) }}" data-konfirmasi="{{ $p->guru->nama }} tidak lagi mengampu {{ $p->mataPelajaran->nama }} di kelas {{ $p->kelas->nama }}. Penugasan yang sudah punya materi, tugas, atau forum tidak bisa dihapus." data-judul="Hapus penugasan?" data-tombol="Hapus">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon hover:!bg-danger-soft hover:!text-danger" title="Hapus" aria-label="Hapus penugasan"><x-ikon nama="hapus" class="size-[18px]" /></button>
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
