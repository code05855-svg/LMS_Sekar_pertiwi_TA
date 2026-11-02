{{-- Form tugas. $tugas (baru atau ada); $daftarPenugasan hanya saat membuat. --}}
@php
    $baru = ! $tugas->exists;
    $jenisDipilih = old('jenis_jawaban', $tugas->jenis_jawaban);
    $ekstensiLampiran = array_merge(config('lms.unggah.ekstensi_dokumen'), config('lms.unggah.ekstensi_foto'));
    $maksLampiran = (int) config('lms.unggah.lampiran_maks');
    $maksMb = (int) round(config('lms.unggah.tugas_kb') / 1024);
    $lampiranAda = $baru ? collect() : $tugas->lampiran;
@endphp
 
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
    <fieldset>
        <legend class="label">Jenis jawaban yang diterima</legend>
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach (\App\Enums\JenisJawaban::cases() as $jenis)
                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-line bg-white p-3.5 transition has-checked:border-navy has-checked:bg-info-soft has-checked:ring-1 has-checked:ring-navy">
                    <input type="checkbox" name="jenis_jawaban[]" value="{{ $jenis->value }}" class="size-4 accent-navy" @checked(in_array($jenis->value, (array) $jenisDipilih, true))>
                    <x-ikon :nama="$jenis->ikon()" class="size-5 text-navy" />
                    <span class="text-sm font-semibold text-ink">{{ $jenis->label() }}</span>
                </label>
            @endforeach
        </div>
        <p class="hint">Siswa hanya bisa memilih jenis yang kamu centang. Biarkan semuanya tercentang bila tidak ingin membatasi.</p>
        @error('jenis_jawaban')<p class="error-text">{{ $message }}</p>@enderror
    </fieldset>
 
    <fieldset class="space-y-4 rounded-2xl border border-line p-4 sm:p-5">
        <legend class="px-2 text-sm font-bold text-ink">Lampiran tugas <span class="font-normal text-muted">(opsional, maksimal {{ $maksLampiran }})</span></legend>
        <p class="text-sm text-muted">Tambahkan soal atau bahan pendukung berupa dokumen, foto, atau tautan (mis. video atau Google Drive). Teks instruksi tetap ditulis di kolom Instruksi di atas.</p>
 
        @if ($lampiranAda->isNotEmpty())
            <ul class="space-y-2">
                @foreach ($lampiranAda as $l)
                    <li class="flex items-center gap-3 rounded-xl bg-canvas px-3 py-2.5">
                        <x-ikon :nama="$l->jenis->ikon()" class="size-5 shrink-0 text-navy" />
                        <span class="min-w-0 flex-1 truncate text-sm font-medium text-ink">{{ $l->namaTampil() }}</span>
                        <label class="flex shrink-0 cursor-pointer items-center gap-2 text-xs font-semibold text-danger">
                            <input type="checkbox" name="hapus_lampiran[]" value="{{ $l->id }}" class="size-4 accent-danger" @checked(in_array($l->id, array_map('intval', (array) old('hapus_lampiran', [])), true))> Hapus
                        </label>
                    </li>
                @endforeach
            </ul>
        @endif
 
        <x-input-file name="lampiran_berkas[]" multiple :accept="collect($ekstensiLampiran)->map(fn ($e) => '.'.$e)->implode(',')" :maks-mb="$maksMb" label="Berkas (dokumen atau foto)"
            :bantuan="'Format: '.implode(', ', $ekstensiLampiran).'. Maksimal '.$maksMb.' MB per berkas.'" />
 
        <div x-data="{ daftar: @js(array_values((array) old('lampiran_tautan', []))), maks: {{ $maksLampiran }} }">
            <p class="label">Tautan (link)</p>
            <template x-for="(url, i) in daftar" :key="i">
                <div class="mb-2 flex gap-2">
                    <input type="url" name="lampiran_tautan[]" x-model="daftar[i]" class="input" placeholder="https://..." maxlength="500">
                    <button type="button" class="btn-secondary btn-sm shrink-0" @click="daftar.splice(i, 1)" aria-label="Hapus tautan"><x-ikon nama="tutup" class="size-4" /></button>
                </div>
            </template>
            <button type="button" class="btn-secondary btn-sm" x-show="daftar.length < maks" @click="daftar.push('')"><x-ikon nama="tambah" class="size-4" /> Tambah tautan</button>
            @error('lampiran_berkas')<p class="error-text">{{ $message }}</p>@enderror
            @error('lampiran_berkas.*')<p class="error-text">{{ $message }}</p>@enderror
            @error('lampiran_tautan')<p class="error-text">{{ $message }}</p>@enderror
            @error('lampiran_tautan.*')<p class="error-text">{{ $message }}</p>@enderror
        </div>
    </fieldset>
</div>
