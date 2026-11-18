{{-- Form kegiatan. $kegiatan (baru atau ada). --}}
<div class="space-y-5">
    <x-field name="judul" label="Nama kegiatan" :value="old('judul', $kegiatan->judul)" placeholder="Contoh: Lomba Kebersihan Kelas" required maxlength="200" />
 
    <div class="grid gap-5 sm:grid-cols-3">
        <x-field type="date" name="tanggal_mulai" label="Tanggal mulai" :value="old('tanggal_mulai', $kegiatan->tanggal_mulai?->format('Y-m-d'))" required />
        <x-field type="date" name="tanggal_selesai" label="Tanggal selesai" :value="old('tanggal_selesai', $kegiatan->tanggal_selesai?->format('Y-m-d'))" hint="Kosongkan bila satu hari." />
        <x-field type="select" name="status" label="Status" required hint="Hanya yang Terbit tampil di beranda.">
            @foreach (\App\Enums\StatusKonten::cases() as $s)
                <option value="{{ $s->value }}" @selected(old('status', $kegiatan->status?->value) === $s->value)>{{ $s->label() }}</option>
            @endforeach
        </x-field>
    </div>
 
    <x-field name="lokasi" label="Lokasi" :value="old('lokasi', $kegiatan->lokasi)" placeholder="Contoh: Lapangan upacara" maxlength="150" hint="Opsional." />
 
    <x-field type="textarea" name="ringkasan" label="Ringkasan" :value="old('ringkasan', $kegiatan->ringkasan)" rows="2" required maxlength="300" hint="Satu atau dua kalimat; tampil di kartu (maks. 300 karakter)." />
 
    <x-field type="textarea" name="deskripsi" label="Deskripsi kegiatan" :value="old('deskripsi', $kegiatan->deskripsi)" rows="8" required hint="Teks biasa. Pisahkan paragraf dengan satu baris kosong." />
 
    @include('admin.partials.gambar-konten', ['model' => $kegiatan])
</div>

