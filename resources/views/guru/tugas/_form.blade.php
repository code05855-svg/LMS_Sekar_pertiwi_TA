{{-- Form tugas. $tugas (baru atau ada); $daftarPenugasan hanya saat membuat. --}}
@php($baru = ! $tugas->exists)

<div class="space-y-5">
    @if ($baru)
        <x-field type="select" name="penugasan_guru_id" label="Kelas dan mata pelajaran" required>
            <option value="">Pilih kelas dan mata pelajaran</option>
            @foreach ($daftarPenugasan as $p)
                <option value="{{ $p->id }}" @selected((string) old('penugasan_guru_id', $tugas->penugasan_guru_id) === (string) $p->id)>{{ $p->mataPelajaran->nama }} — Kelas {{ $p->kelas->nama }}</option>
            @endforeach
        </x-field>
    @else
        <div>
            <p class="label">Kelas dan mata pelajaran</p>
            <p class="input flex items-center bg-canvas text-muted">{{ $tugas->penugasanGuru->mataPelajaran->nama }} — Kelas {{ $tugas->penugasanGuru->kelas->nama }}</p>
        </div>
    @endif

    <x-field name="judul" label="Judul tugas" :value="old('judul', $tugas->judul)" placeholder="Contoh: Laporan pengamatan fotosintesis" required maxlength="200" />

    <x-field type="textarea" name="instruksi" label="Instruksi" :value="old('instruksi', $tugas->instruksi)" rows="6" placeholder="Jelaskan apa yang harus dikerjakan, format jawaban, dan kriteria penilaian." hint="Opsional, tetapi sangat membantu siswa." />

    <div class="grid gap-5 sm:grid-cols-2">
        <x-field type="datetime-local" name="tenggat" label="Tenggat pengumpulan" :value="old('tenggat', $tugas->tenggat?->format('Y-m-d\TH:i'))" required
                 hint="Pengumpulan setelah waktu ini otomatis tercatat terlambat." />
        <x-field type="number" name="nilai_maksimum" label="Nilai maksimum" :value="old('nilai_maksimum', $tugas->nilai_maksimum)" required min="1" max="1000" hint="Biasanya 100." />
    </div>
</div>
