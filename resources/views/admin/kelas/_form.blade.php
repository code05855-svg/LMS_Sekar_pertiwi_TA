<div class="grid gap-5 sm:grid-cols-3">
    <x-field type="select" name="tingkat" label="Tingkat" required>
        <option value="">Pilih tingkat</option>
        @foreach (\App\Enums\TingkatKelas::cases() as $t)
            <option value="{{ $t->value }}" @selected(old('tingkat', $kelas->tingkat?->value) === $t->value)>Kelas {{ $t->value }}</option>
        @endforeach
    </x-field>
    <x-field name="nama" label="Nama kelas" :value="old('nama', $kelas->nama)" placeholder="Contoh: VII A" required hint="Dipakai sebagai label, mis. VII A." />
    <x-field name="tahun_ajaran" label="Tahun ajaran" :value="old('tahun_ajaran', $kelas->tahun_ajaran)" placeholder="2026/2027" required hint="Format 2026/2027." />
</div>
