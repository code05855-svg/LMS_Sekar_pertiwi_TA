<div class="grid gap-5 sm:grid-cols-3">
    <x-field name="kode" label="Kode" :value="old('kode', $mapel->kode)" placeholder="MTK" required hint="Singkat, unik. Huruf besar." class="sm:col-span-1" />
    <x-field name="nama" label="Nama mata pelajaran" :value="old('nama', $mapel->nama)" placeholder="Matematika" required class="sm:col-span-2" />
</div>
