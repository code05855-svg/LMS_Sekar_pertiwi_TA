@extends('layouts.app')
 
@section('judul', 'Profil')
 
@section('konten')
<x-judul-halaman judul="Profil saya" deskripsi="Lihat data akun, ganti foto profil, dan ganti kata sandi." />
 
<div class="grid gap-5 lg:grid-cols-5">
    <div class="space-y-5 lg:col-span-2">
    <x-card judul="Data akun" deskripsi="Data diatur oleh admin sekolah. Hubungi admin bila ada yang perlu diperbaiki.">
        <div class="mb-5 flex items-center gap-4">
            <x-avatar :user="$pengguna" ukuran="size-16 text-xl" />
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
 
    <x-card judul="Foto profil" deskripsi="Tampil di menu atas, forum, dan daftar kelas. JPG, PNG, atau WEBP, maksimal {{ round(config('lms.unggah.foto_profil_kb') / 1024) }} MB.">
        {{-- pilih(): foto besar (mis. dari kamera ponsel) dipangkas persegi dan diperkecil ke maks 1024 px di peramban
             sebelum diunggah, sehingga unggahan hanya ±100 KB. Bila gagal, berkas asli dikirim apa adanya
             dan server tetap memeriksanya. --}}
        <div x-data="{
            pratinjau: null,
            async pilih(e) {
                const input = e.target;
                const f = input.files[0];
                if (! f) { this.pratinjau = null; return; }
                let hasil = f;
                try {
                    if (/^image\/(jpeg|png|webp)$/.test(f.type)) {
                        const bmp = await createImageBitmap(f, { imageOrientation: 'from-image' });
                        if (bmp.width > 1024 || bmp.height > 1024 || f.size > 1048576) {
                            const sisi = Math.min(bmp.width, bmp.height);
                            const t = Math.min(1024, sisi);
                            const k = document.createElement('canvas');
                            k.width = t; k.height = t;
                            const c = k.getContext('2d');
                            c.fillStyle = '#fff';
                            c.fillRect(0, 0, t, t);
                            c.drawImage(bmp, (bmp.width - sisi) / 2, (bmp.height - sisi) / 2, sisi, sisi, 0, 0, t, t);
                            const blob = await new Promise((r) => k.toBlob(r, 'image/jpeg', 0.9));
                            if (blob) {
                                hasil = new File([blob], 'foto-profil.jpg', { type: 'image/jpeg' });
                                const dt = new DataTransfer();
                                dt.items.add(hasil);
                                input.files = dt.files;
                            }
                        }
                        if (bmp.close) { bmp.close(); }
                    }
                } catch (err) { hasil = f; }
                this.pratinjau = URL.createObjectURL(hasil);
            }
        }">
            <div class="flex items-center gap-4">
                <template x-if="pratinjau"><img :src="pratinjau" alt="Pratinjau foto" class="size-20 shrink-0 rounded-full bg-canvas object-cover"></template>
                <div x-show="! pratinjau"><x-avatar :user="$pengguna" ukuran="size-20 text-2xl" /></div>
                <form method="POST" action="{{ route('profil.foto.store') }}" enctype="multipart/form-data" class="min-w-0 flex-1 space-y-3">
                    @csrf
                    <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required
                           class="block w-full text-sm text-muted file:mr-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-info-soft file:px-3.5 file:py-2 file:text-sm file:font-semibold file:text-navy hover:file:bg-info-soft/70"
                           @change="pilih($event)">
                    <button type="submit" class="btn-primary btn-sm" data-memuat="Menyimpan..."><x-ikon nama="kamera" class="size-4" /> Simpan foto</button>
                </form>
            </div>
            @error('foto')<p class="error-text mt-2">{{ $message }}</p>@enderror
        </div>
 
        @if ($pengguna->foto_profil)
            <form method="POST" action="{{ route('profil.foto.destroy') }}" class="mt-4 border-t border-line pt-4" data-konfirmasi="Foto profilmu akan dihapus dan inisial namamu dipakai kembali." data-judul="Hapus foto profil?" data-tombol="Hapus foto">
                @csrf @method('DELETE')
                <button type="submit" class="btn-danger-soft btn-sm"><x-ikon nama="hapus" class="size-4" /> Hapus foto</button>
            </form>
        @endif
    </x-card>
    </div>
 
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
