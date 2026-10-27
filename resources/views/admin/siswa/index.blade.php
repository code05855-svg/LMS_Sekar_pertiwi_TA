@extends('layouts.app')

@section('judul', 'Akun Siswa')

@section('konten')
<x-judul-halaman judul="Akun siswa" deskripsi="Kelola akun, kelas, status akses, dan password siswa.">
    <a href="{{ route('admin.penempatan-siswa.index') }}" class="btn-secondary"><x-ikon nama="pindah" class="size-4" /> Penempatan kelas</a>
    <a href="{{ route('admin.siswa.create') }}" class="btn-primary"><x-ikon nama="tambah" class="size-4" /> Tambah siswa</a>
</x-judul-halaman>

<div class="grid gap-4 sm:grid-cols-3">
    <x-kartu-stat label="Total siswa" :nilai="$ringkasan['total']" catatan="Semua akun peserta didik" warna="navy" />
    <x-kartu-stat label="Aktif" :nilai="$ringkasan['aktif']" catatan="Bisa login" warna="success" />
    <x-kartu-stat label="Nonaktif" :nilai="$ringkasan['total'] - $ringkasan['aktif']" catatan="Tidak bisa login" warna="danger" />
</div>

<form method="GET" class="card mt-5 flex flex-wrap items-center gap-3 p-3">
    <div class="relative min-w-52 flex-1">
        <x-ikon nama="cari" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted" />
        <input type="search" name="cari" value="{{ request('cari') }}" placeholder="Cari nama atau NIS..." class="input pl-10" aria-label="Cari siswa">
    </div>
    <select name="kelas" class="input w-auto" aria-label="Filter kelas" onchange="this.form.submit()">
        <option value="">Semua kelas</option>
        <option value="tanpa" @selected(request('kelas') === 'tanpa')>Belum ditempatkan</option>
        @foreach ($daftarKelas as $k)
            <option value="{{ $k->id }}" @selected((string) request('kelas') === (string) $k->id)>{{ $k->tingkat->value }} {{ $k->nama }}</option>
        @endforeach
    </select>
    <select name="status" class="input w-auto" aria-label="Filter status" onchange="this.form.submit()">
        <option value="">Semua status</option>
        <option value="aktif" @selected(request('status') === 'aktif')>Aktif</option>
        <option value="nonaktif" @selected(request('status') === 'nonaktif')>Nonaktif</option>
    </select>
    <button class="btn-secondary" type="submit">Cari</button>
    @if (request()->hasAny(['cari', 'status', 'kelas']))
        <a href="{{ route('admin.siswa.index') }}" class="tautan text-sm">Atur ulang</a>
    @endif
</form>

<x-card judul="Daftar siswa" class="mt-5" :tanpa-padding="true">
    @if ($daftar->isEmpty())
        <x-kosong judul="Tidak ada akun siswa" :deskripsi="request()->hasAny(['cari', 'status', 'kelas']) ? 'Tidak ada yang cocok dengan penyaringan. Coba kata kunci lain.' : 'Tambahkan akun siswa agar bisa mengikuti pembelajaran.'" ikon="pengguna">
            @unless (request()->hasAny(['cari', 'status', 'kelas']))
                <a href="{{ route('admin.siswa.create') }}" class="btn-primary">Tambah siswa</a>
            @endunless
        </x-kosong>
    @else
        <x-tabel-data :kolom="['Nama', 'NIS', 'Kelas', 'Status', ['label' => 'Aksi', 'kelas' => 'text-right']]" class="px-3">
            @foreach ($daftar as $akun)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <x-avatar :nama="$akun->nama" ukuran="size-9 text-xs" />
                            <div class="min-w-0">
                                <p class="font-semibold text-ink">{{ $akun->nama }}</p>
                                @if ($akun->email)<p class="text-xs text-muted">{{ $akun->email }}</p>@endif
                            </div>
                        </div>
                    </td>
                    <td class="text-muted">{{ $akun->nomor_induk }}</td>
                    <td>
                        @if ($akun->kelas)
                            <span class="badge-info">{{ $akun->kelas->tingkat->value }} {{ $akun->kelas->nama }}</span>
                        @else
                            <span class="badge-gold">Belum ditempatkan</span>
                        @endif
                    </td>
                    <td><x-badge-status :status="$akun->aktif" /></td>
                    <td>
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('admin.siswa.edit', $akun) }}" class="btn-icon" title="Ubah" aria-label="Ubah {{ $akun->nama }}"><x-ikon nama="ubah" class="size-[18px]" /></a>
                            <button type="button" class="btn-icon" title="Atur ulang password" aria-label="Atur ulang password {{ $akun->nama }}"
                                    @click="$dispatch('buka-modal', 'password-{{ $akun->id }}')"><x-ikon nama="kata-sandi" class="size-[18px]" /></button>
                            <form method="POST" action="{{ route('admin.siswa.status', $akun) }}" data-konfirmasi="{{ $akun->aktif ? $akun->nama.' tidak akan bisa login sampai diaktifkan kembali.' : 'Akun '.$akun->nama.' akan bisa login lagi.' }}"
                                  data-judul="{{ $akun->aktif ? 'Nonaktifkan akun?' : 'Aktifkan akun?' }}" data-tombol="{{ $akun->aktif ? 'Nonaktifkan' : 'Aktifkan' }}" data-bahaya="{{ $akun->aktif ? 'ya' : 'tidak' }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn-icon" title="{{ $akun->aktif ? 'Nonaktifkan' : 'Aktifkan' }}" aria-label="{{ $akun->aktif ? 'Nonaktifkan' : 'Aktifkan' }} {{ $akun->nama }}"><x-ikon :nama="$akun->aktif ? 'kunci' : 'buka-kunci'" class="size-[18px]" /></button>
                            </form>
                            <form method="POST" action="{{ route('admin.siswa.destroy', $akun) }}" data-konfirmasi="Akun {{ $akun->nama }} akan dihapus permanen. Siswa yang sudah mengumpulkan tugas atau aktif di forum tidak bisa dihapus; nonaktifkan saja." data-judul="Hapus akun siswa?" data-tombol="Hapus">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon hover:!bg-danger-soft hover:!text-danger" title="Hapus" aria-label="Hapus {{ $akun->nama }}"><x-ikon nama="hapus" class="size-[18px]" /></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-tabel-data>
        <div class="px-5 pb-5">{{ $daftar->links() }}</div>
    @endif
</x-card>

@foreach ($daftar as $akun)
    <x-modal name="password-{{ $akun->id }}" judul="Atur ulang password">
        <form method="POST" action="{{ route('admin.siswa.password', $akun) }}" class="space-y-4">
            @csrf @method('PATCH')
            <p class="text-sm text-muted">Isi password baru untuk <strong class="text-ink">{{ $akun->nama }}</strong>, lalu sampaikan kepada yang bersangkutan.</p>
            <x-field type="password" name="password" :id="'pw-'.$akun->id" label="Password baru" autocomplete="new-password" required hint="Minimal 8 karakter." />
            <div class="flex justify-end gap-2">
                <button type="button" class="btn-secondary" @click="$dispatch('tutup-modal', 'password-{{ $akun->id }}')">Batal</button>
                <button type="submit" class="btn-primary">Simpan password</button>
            </div>
        </form>
    </x-modal>
@endforeach
@endsection
