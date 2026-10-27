@extends('layouts.app')

@section('judul', 'Kelas')

@section('konten')
<x-judul-halaman judul="Data kelas" deskripsi="Kelas, tahun ajaran, jumlah siswa, dan mata pelajaran yang diajarkan.">
    <a href="{{ route('admin.kelas.create') }}" class="btn-primary"><x-ikon nama="tambah" class="size-4" /> Tambah kelas</a>
</x-judul-halaman>

@if ($daftarTahun->count() > 1 || request()->filled('tahun_ajaran'))
    <form method="GET" class="mb-5 flex items-center gap-3">
        <label for="tahun" class="text-sm font-semibold text-ink">Tahun ajaran</label>
        <select id="tahun" name="tahun_ajaran" class="input w-auto" onchange="this.form.submit()">
            <option value="">Semua</option>
            @foreach ($daftarTahun as $t)
                <option value="{{ $t }}" @selected(request('tahun_ajaran') === $t)>{{ $t }}</option>
            @endforeach
        </select>
    </form>
@endif

@if ($daftar->isEmpty())
    <x-card>
        <x-kosong judul="Belum ada kelas" deskripsi="Tambahkan kelas pertama, misalnya VII A tahun ajaran 2026/2027." ikon="kelas">
            <a href="{{ route('admin.kelas.create') }}" class="btn-primary">Tambah kelas</a>
        </x-kosong>
    </x-card>
@else
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($daftar as $k)
            <div class="card flex flex-col p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted">Kelas {{ $k->tingkat->value }}</p>
                        <h2 class="text-2xl font-bold tracking-tight text-navy">{{ $k->nama }}</h2>
                    </div>
                    <span class="badge-gold">{{ $k->tahun_ajaran }}</span>
                </div>
                <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-xl bg-canvas p-3"><dt class="text-xs text-muted">Siswa</dt><dd class="text-xl font-bold text-ink">{{ $k->siswa_count }}</dd></div>
                    <div class="rounded-xl bg-canvas p-3"><dt class="text-xs text-muted">Mata pelajaran</dt><dd class="text-xl font-bold text-ink">{{ $k->penugasan_guru_count }}</dd></div>
                </dl>
                <div class="mt-5 flex items-center justify-between gap-2 border-t border-line pt-4">
                    <a href="{{ route('admin.kelas.show', $k) }}" class="btn-primary btn-sm">Lihat detail <x-ikon nama="panah" class="size-4" /></a>
                    <div class="flex gap-1">
                        <a href="{{ route('admin.kelas.edit', $k) }}" class="btn-icon" title="Ubah" aria-label="Ubah kelas {{ $k->nama }}"><x-ikon nama="ubah" class="size-[18px]" /></a>
                        <form method="POST" action="{{ route('admin.kelas.destroy', $k) }}" data-konfirmasi="Kelas {{ $k->nama }} akan dihapus. Siswa di kelas ini menjadi belum ditempatkan. Kelas yang masih punya penugasan guru tidak bisa dihapus." data-judul="Hapus kelas?" data-tombol="Hapus">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-icon hover:!bg-danger-soft hover:!text-danger" title="Hapus" aria-label="Hapus kelas {{ $k->nama }}"><x-ikon nama="hapus" class="size-[18px]" /></button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    {{ $daftar->links() }}
@endif
@endsection
