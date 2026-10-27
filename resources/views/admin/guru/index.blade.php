@extends('layouts.app')

@section('judul', 'Akun Guru')

@section('konten')
<x-judul-halaman judul="Akun guru" deskripsi="Kelola akun, status akses, dan password guru.">
    <a href="{{ route('admin.guru.create') }}" class="btn-primary"><x-ikon nama="tambah" class="size-4" /> Tambah guru</a>
</x-judul-halaman>

<div class="grid gap-4 sm:grid-cols-3">
    <x-kartu-stat label="Total guru" :nilai="$ringkasan['total']" catatan="Semua akun pengajar" warna="navy" />
    <x-kartu-stat label="Aktif" :nilai="$ringkasan['aktif']" catatan="Bisa login" warna="success" />
    <x-kartu-stat label="Nonaktif" :nilai="$ringkasan['total'] - $ringkasan['aktif']" catatan="Tidak bisa login" warna="danger" />
</div>

<form method="GET" class="card mt-5 flex flex-wrap items-center gap-3 p-3">
    <div class="relative min-w-52 flex-1">
        <x-ikon nama="cari" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted" />
        <input type="search" name="cari" value="{{ request('cari') }}" placeholder="Cari nama atau NIP..." class="input pl-10" aria-label="Cari guru">
    </div>
    <select name="status" class="input w-auto" aria-label="Filter status" onchange="this.form.submit()">
        <option value="">Semua status</option>
        <option value="aktif" @selected(request('status') === 'aktif')>Aktif</option>
        <option value="nonaktif" @selected(request('status') === 'nonaktif')>Nonaktif</option>
    </select>
    <button class="btn-secondary" type="submit">Cari</button>
    @if (request()->hasAny(['cari', 'status']))
        <a href="{{ route('admin.guru.index') }}" class="tautan text-sm">Atur ulang</a>
    @endif
</form>

<x-card judul="Daftar guru" class="mt-5" :tanpa-padding="true">
    @if ($daftar->isEmpty())
        <x-kosong judul="Tidak ada akun guru" :deskripsi="request()->hasAny(['cari', 'status']) ? 'Tidak ada yang cocok dengan pencarian. Coba kata kunci lain.' : 'Tambahkan akun guru pertama agar bisa diberi penugasan mengajar.'" ikon="orang">
            @unless (request()->hasAny(['cari', 'status']))
                <a href="{{ route('admin.guru.create') }}" class="btn-primary">Tambah guru</a>
            @endunless
        </x-kosong>
    @else
        <x-tabel-data :kolom="['Nama', 'NIP', 'Email', 'Penugasan', 'Status', ['label' => 'Aksi', 'kelas' => 'text-right']]" class="px-3">
            @foreach ($daftar as $akun)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <x-avatar :nama="$akun->nama" ukuran="size-9 text-xs" />
                            <span class="font-semibold text-ink">{{ $akun->nama }}</span>
                        </div>
                    </td>
                    <td class="text-muted">{{ $akun->nomor_induk }}</td>
                    <td class="text-muted">{{ $akun->email ?: '-' }}</td>
                    <td>{{ $akun->penugasan_guru_count }} kelas</td>
                    <td><x-badge-status :status="$akun->aktif" /></td>
                    <td>
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('admin.guru.edit', $akun) }}" class="btn-icon" title="Ubah" aria-label="Ubah {{ $akun->nama }}"><x-ikon nama="ubah" class="size-[18px]" /></a>
                            <button type="button" class="btn-icon" title="Atur ulang password" aria-label="Atur ulang password {{ $akun->nama }}"
                                    @click="$dispatch('buka-modal', 'password-{{ $akun->id }}')"><x-ikon nama="kata-sandi" class="size-[18px]" /></button>
                            <form method="POST" action="{{ route('admin.guru.status', $akun) }}" data-konfirmasi="{{ $akun->aktif ? $akun->nama.' tidak akan bisa login sampai diaktifkan kembali.' : 'Akun '.$akun->nama.' akan bisa login lagi.' }}"
                                  data-judul="{{ $akun->aktif ? 'Nonaktifkan akun?' : 'Aktifkan akun?' }}" data-tombol="{{ $akun->aktif ? 'Nonaktifkan' : 'Aktifkan' }}" data-bahaya="{{ $akun->aktif ? 'ya' : 'tidak' }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn-icon" title="{{ $akun->aktif ? 'Nonaktifkan' : 'Aktifkan' }}" aria-label="{{ $akun->aktif ? 'Nonaktifkan' : 'Aktifkan' }} {{ $akun->nama }}"><x-ikon :nama="$akun->aktif ? 'kunci' : 'buka-kunci'" class="size-[18px]" /></button>
                            </form>
                            <form method="POST" action="{{ route('admin.guru.destroy', $akun) }}" data-konfirmasi="Akun {{ $akun->nama }} akan dihapus permanen. Akun yang sudah punya penugasan, tugas, atau forum tidak bisa dihapus." data-judul="Hapus akun guru?" data-tombol="Hapus">
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
        <form method="POST" action="{{ route('admin.guru.password', $akun) }}" class="space-y-4">
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
