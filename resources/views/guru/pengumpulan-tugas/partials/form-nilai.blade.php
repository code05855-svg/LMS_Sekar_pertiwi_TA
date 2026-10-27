{{-- Form nilai + komentar. Butuh $pengumpulan (tugas dimuat) dan $sisaBelumDinilai. --}}
@php($tugas = $pengumpulan->tugas)

<form method="POST" action="{{ route('guru.penilaian.update', $pengumpulan) }}" class="card sticky top-24 p-5 sm:p-6">
    @csrf @method('PUT')

    <h2 class="text-lg font-bold tracking-tight text-ink">Penilaian</h2>
    @if ($pengumpulan->telahDinilai())
        <p class="mt-0.5 text-xs text-muted">Dinilai {{ $pengumpulan->dinilai_pada->translatedFormat('d M Y, H:i') }}. Kamu bisa mengubahnya.</p>
    @else
        <p class="mt-0.5 text-xs text-muted">Belum dinilai.</p>
    @endif

    <div class="mt-5 space-y-5">
        <div>
            <label for="f-nilai" class="label">Nilai (0–{{ $tugas->nilai_maksimum }})<span class="text-danger" aria-hidden="true"> *</span></label>
            <div class="flex items-center gap-3">
                <input id="f-nilai" name="nilai" type="number" inputmode="decimal" step="0.01" min="0" max="{{ $tugas->nilai_maksimum }}" required
                       value="{{ old('nilai', $pengumpulan->nilai !== null ? \App\Support\Format::nilai($pengumpulan->nilai) : '') }}"
                       @class(['input w-32 text-lg font-bold', 'input-error' => $errors->has('nilai')]) placeholder="0">
                <span class="text-sm text-muted">dari {{ $tugas->nilai_maksimum }}</span>
            </div>
            @error('nilai')<p class="error-text">{{ $message }}</p>@enderror
        </div>

        <x-field type="textarea" name="komentar" label="Komentar untuk siswa" :value="old('komentar', $pengumpulan->komentar)" rows="5" placeholder="Apa yang sudah baik, dan apa yang perlu diperbaiki?" hint="Opsional. Siswa melihatnya bersama nilai." />
    </div>

    <div class="mt-6 flex flex-col gap-2">
        <button type="submit" class="btn-primary w-full" data-memuat="Menyimpan...">Simpan nilai</button>
        @if ($sisaBelumDinilai > 0)
            <button type="submit" name="lanjut" value="berikutnya" class="btn-secondary w-full" data-memuat="Menyimpan...">Simpan dan buka berikutnya <span class="text-xs text-muted">({{ $sisaBelumDinilai }} lagi)</span></button>
        @endif
    </div>
</form>
