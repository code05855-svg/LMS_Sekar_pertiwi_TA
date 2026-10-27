@extends('layouts.app')

@section('judul', 'Materi')

@section('konten')
<x-judul-halaman judul="Materi pembelajaran" deskripsi="Semua materi yang kamu buat. PDF yang terbit otomatis diindeks untuk chatbot.">
    <a href="{{ route('guru.materi.create') }}" class="btn-primary"><x-ikon nama="tambah" class="size-4" /> Buat materi</a>
</x-judul-halaman>

<form method="GET" class="card mb-5 flex flex-wrap items-center gap-3 p-3">
    <div class="relative min-w-52 flex-1">
        <x-ikon nama="cari" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted" />
        <input type="search" name="cari" value="{{ request('cari') }}" placeholder="Cari judul materi..." class="input pl-10" aria-label="Cari materi">
    </div>
    <select name="penugasan" class="input w-auto" aria-label="Kelas dan mata pelajaran" onchange="this.form.submit()">
        <option value="">Semua kelas</option>
        @foreach ($daftarPenugasan as $p)
            <option value="{{ $p->id }}" @selected((string) request('penugasan') === (string) $p->id)>{{ $p->mataPelajaran->nama }} — {{ $p->kelas->nama }}</option>
        @endforeach
    </select>
    <select name="status" class="input w-auto" aria-label="Status" onchange="this.form.submit()">
        <option value="">Semua status</option>
        <option value="terbit" @selected(request('status') === 'terbit')>Terbit</option>
        <option value="draf" @selected(request('status') === 'draf')>Draf</option>
    </select>
    <button class="btn-secondary" type="submit">Cari</button>
    @if (request()->hasAny(['cari', 'penugasan', 'status']))<a href="{{ route('guru.materi.index') }}" class="tautan text-sm">Atur ulang</a>@endif
</form>

<x-card :tanpa-padding="true">
    @if ($daftar->isEmpty())
        <x-kosong judul="Belum ada materi" :deskripsi="request()->hasAny(['cari', 'penugasan', 'status']) ? 'Tidak ada materi yang cocok dengan filter.' : 'Buat materi pertama dan unggah PDF-nya.'" ikon="dokumen">
            <a href="{{ route('guru.materi.create') }}" class="btn-primary btn-sm">Buat materi</a>
        </x-kosong>
    @else
        <x-tabel-data :kolom="['Materi', 'Kelas', 'Berkas', 'Status', ['label' => 'Aksi', 'kelas' => 'text-right']]" class="px-3 pt-3">
            @foreach ($daftar as $m)
                <tr>
                    <td class="max-w-xs">
                        <a href="{{ route('guru.materi.show', $m) }}" class="block truncate font-semibold text-ink hover:text-navy">{{ $m->judul }}</a>
                        <span class="text-xs text-muted">{{ $m->created_at->translatedFormat('d M Y') }}</span>
                    </td>
                    <td class="whitespace-nowrap">
                        <span class="font-medium text-ink">{{ $m->penugasanGuru->mataPelajaran->nama }}</span>
                        <span class="block text-xs text-muted">Kelas {{ $m->penugasanGuru->kelas->nama }}</span>
                    </td>
                    <td class="whitespace-nowrap text-muted">
                        {{ $m->berkas_materi_count }} PDF
                        @if ($m->berkas_materi_count)
                            <span class="block text-xs {{ $m->berkas_terindeks_count < $m->berkas_materi_count ? 'text-gold-600' : 'text-success' }}">{{ $m->berkas_terindeks_count }} terindeks</span>
                        @endif
                    </td>
                    <td><x-badge-status :status="$m->status" /></td>
                    <td>
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('guru.materi.show', $m) }}" class="btn-icon" title="Lihat" aria-label="Lihat {{ $m->judul }}"><x-ikon nama="lihat" class="size-[18px]" /></a>
                            <a href="{{ route('guru.materi.edit', $m) }}" class="btn-icon" title="Ubah" aria-label="Ubah {{ $m->judul }}"><x-ikon nama="ubah" class="size-[18px]" /></a>
                            <form method="POST" action="{{ route('guru.materi.destroy', $m) }}" data-konfirmasi="Materi “{{ $m->judul }}” beserta semua PDF dan indeks chatbot-nya akan dihapus permanen." data-judul="Hapus materi?" data-tombol="Hapus">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon hover:!bg-danger-soft hover:!text-danger" title="Hapus" aria-label="Hapus {{ $m->judul }}"><x-ikon nama="hapus" class="size-[18px]" /></button>
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
