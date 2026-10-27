@extends('layouts.app')

@section('judul', 'Profil')

@section('konten')
<x-judul-halaman judul="Profil saya" deskripsi="Lihat data akun dan ganti kata sandi." />

<div class="grid gap-5 lg:grid-cols-5">
    <x-card judul="Data akun" deskripsi="Data diatur oleh admin sekolah. Hubungi admin bila ada yang perlu diperbaiki." class="lg:col-span-2">
        <div class="mb-5 flex items-center gap-4">
            <x-avatar :nama="$pengguna->nama" ukuran="size-16 text-xl" />
            <div class="min-w-0">
                <p class="truncate text-lg font-bold text-ink">{{ $pengguna->nama }}</p>
                <x-badge-status :status="$pengguna->peran" />
            </div>
        </div>
        <dl class="space-y-3 text-sm">
            <div class="flex justify-between gap-4"><dt class="text-muted">Nomor induk</dt><dd class="font-semibold text-ink">{{ $pengguna->nomor_induk }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-muted">Email</dt><dd class="truncate font-semibold text-ink">{{ $pengguna->email ?: '-' }}</dd></div>
            @if ($pengguna->adalahSiswa())
                <div class="flex justify-between gap-4"><dt class="text-muted">Kelas</dt><dd class="font-semibold text-ink">{{ $pengguna->kelas ? $pengguna->kelas->tingkat->value.' '.$pengguna->kelas->nama : 'Belum ditempatkan' }}</dd></div>
            @endif
            <div class="flex justify-between gap-4"><dt class="text-muted">Status</dt><dd><x-badge-status :status="$pengguna->aktif" /></dd></div>
        </dl>
    </x-card>

    <x-card judul="Ganti kata sandi" deskripsi="Gunakan minimal 8 karakter yang memuat huruf dan angka." class="lg:col-span-3">
        <form method="POST" action="{{ route('profil.update') }}" class="space-y-5">
            @csrf
            @method('PUT')
            <x-field type="password" name="password_saat_ini" label="Kata sandi saat ini" autocomplete="current-password" required />
            <div class="grid gap-5 sm:grid-cols-2">
                <x-field type="password" name="password" label="Kata sandi baru" autocomplete="new-password" required />
                <x-field type="password" name="password_confirmation" label="Ulangi kata sandi baru" autocomplete="new-password" required />
            </div>
            <div class="flex justify-end">
                <button type="submit" data-memuat="Menyimpan..." class="btn-primary">Simpan kata sandi</button>
            </div>
        </form>
    </x-card>
</div>
@endsection
