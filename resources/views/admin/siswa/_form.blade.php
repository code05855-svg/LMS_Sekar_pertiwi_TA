{{-- Dipakai create dan edit akun siswa. --}}
@php($edit = $akun->exists)

<div class="grid gap-5 sm:grid-cols-2">
    <x-field name="nomor_induk" label="NIS / nomor induk" :value="old('nomor_induk', $akun->nomor_induk)" required
             hint="Dipakai untuk login. Huruf, angka, titik, strip." />
    <x-field name="nama" label="Nama lengkap" :value="old('nama', $akun->nama)" required />
    <x-field type="email" name="email" label="Email" :value="old('email', $akun->email)" hint="Opsional." />
    <x-field type="select" name="kelas_id" label="Kelas" hint="Boleh dikosongkan; bisa diatur nanti di Penempatan Siswa.">
        <option value="">Belum ditempatkan</option>
        @foreach ($daftarKelas as $k)
            <option value="{{ $k->id }}" @selected((string) old('kelas_id', $akun->kelas_id) === (string) $k->id)>{{ $k->tingkat->value }} {{ $k->nama }} · {{ $k->tahun_ajaran }}</option>
        @endforeach
    </x-field>
    <x-field type="password" name="password" :label="$edit ? 'Password baru' : 'Password'" :required="! $edit" autocomplete="new-password"
             :hint="$edit ? 'Kosongkan bila tidak ingin mengubah password.' : 'Minimal 8 karakter.'" />
</div>

<label class="mt-5 flex items-center gap-3 rounded-xl bg-canvas px-4 py-3 text-sm">
    <input type="hidden" name="aktif" value="0">
    <input type="checkbox" name="aktif" value="1" @checked(old('aktif', $akun->aktif ?? true)) class="size-4 rounded border-line text-navy focus:ring-navy">
    <span><span class="font-semibold text-ink">Akun aktif</span> <span class="text-muted">· akun nonaktif tidak bisa login.</span></span>
</label>
