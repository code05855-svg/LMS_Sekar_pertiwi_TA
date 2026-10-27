{{-- Form materi. $materi (baru atau ada), $daftarPenugasan hanya saat membuat. --}}
@php($baru = ! $materi->exists)

<div class="space-y-5">
    @if ($baru)
        <x-field type="select" name="penugasan_guru_id" label="Kelas dan mata pelajaran" required>
            <option value="">Pilih kelas dan mata pelajaran</option>
            @foreach ($daftarPenugasan as $p)
                <option value="{{ $p->id }}" @selected((string) old('penugasan_guru_id', $materi->penugasan_guru_id) === (string) $p->id)>{{ $p->mataPelajaran->nama }} — Kelas {{ $p->kelas->nama }}</option>
            @endforeach
        </x-field>
    @else
        <div>
            <p class="label">Kelas dan mata pelajaran</p>
            <p class="input flex items-center bg-canvas text-muted">{{ $materi->penugasanGuru->mataPelajaran->nama }} — Kelas {{ $materi->penugasanGuru->kelas->nama }}</p>
            <p class="hint">Materi tidak bisa dipindah ke kelas lain. Buat materi baru bila perlu.</p>
        </div>
    @endif

    <x-field name="judul" label="Judul materi" :value="old('judul', $materi->judul)" placeholder="Contoh: Fotosintesis pada tumbuhan" required maxlength="200" />

    <x-field type="textarea" name="deskripsi" label="Deskripsi" :value="old('deskripsi', $materi->deskripsi)" rows="5" placeholder="Ringkasan isi materi, tujuan belajar, atau catatan untuk siswa." hint="Opsional. Tampil di halaman materi siswa." />

    <fieldset>
        <legend class="label">Status</legend>
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ([
                'draf' => ['Draf', 'Hanya terlihat olehmu. Chatbot belum memakainya.'],
                'terbit' => ['Terbit', 'Siswa kelas ini bisa melihat dan mengunduh. Chatbot memakainya untuk menjawab.'],
            ] as $nilai => [$nama, $ket])
                <label class="flex cursor-pointer gap-3 rounded-xl border border-line bg-white p-4 transition has-[:checked]:border-navy has-[:checked]:bg-info-soft has-[:checked]:ring-1 has-[:checked]:ring-navy">
                    <input type="radio" name="status" value="{{ $nilai }}" class="mt-0.5 size-4 accent-navy" @checked(old('status', $materi->status?->value ?? 'draf') === $nilai)>
                    <span>
                        <span class="block text-sm font-semibold text-ink">{{ $nama }}</span>
                        <span class="block text-xs text-muted">{{ $ket }}</span>
                    </span>
                </label>
            @endforeach
        </div>
        @error('status')<p class="error-text">{{ $message }}</p>@enderror
    </fieldset>

    <x-input-file name="berkas[]" multiple accept=".pdf,application/pdf" :maks-mb="(int) round(config('lms.unggah.materi_kb') / 1024)"
        :label="$baru ? 'Berkas PDF materi' : 'Tambah berkas PDF'"
        :bantuan="'Hanya PDF yang berisi teks (bukan hasil scan), maksimal '.(int) round(config('lms.unggah.materi_kb') / 1024).' MB per berkas, sampai 10 berkas.'" />
</div>
