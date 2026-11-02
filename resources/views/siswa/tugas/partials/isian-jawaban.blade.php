{{--
    Isian jawaban menurut jenis: dokumen, foto, tautan, atau teks (hanya jenis yang diterima tugas).
    Hanya panel aktif yang dirender (x-if), jadi hanya isiannya yang ikut terkirim.
    Variabel: $tugas, $awal (jenis terpilih saat dibuka), $pengumpulan (opsional, untuk mengisi ulang).
--}}
@php
    $diterima = $tugas->jenis_jawaban;
    $awal = old('jenis', $awal ?? $diterima[0]);
    $awal = in_array($awal, $diterima, true) ? $awal : $diterima[0];
    $maksMb = (int) round(config('lms.unggah.tugas_kb') / 1024);
    $maksTeks = (int) config('lms.unggah.teks_jawaban_maks');
    $dokumen = collect(config('lms.unggah.ekstensi_dokumen'));
    $foto = collect(config('lms.unggah.ekstensi_foto'));
@endphp
 
<div x-data="{ jenis: '{{ $awal }}' }">
    <input type="hidden" name="jenis" :value="jenis">
 
    @if (count($diterima) > 1)
        <div class="mb-4 flex flex-wrap gap-2" role="tablist" aria-label="Jenis jawaban">
            @foreach ($diterima as $nilai)
                @php($j = \App\Enums\JenisJawaban::from($nilai))
                <button type="button" role="tab" @click="jenis = '{{ $nilai }}'" :aria-selected="jenis === '{{ $nilai }}'"
                        class="inline-flex items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-sm font-semibold transition"
                        :class="jenis === '{{ $nilai }}' ? 'border-navy bg-navy text-white' : 'border-line bg-white text-ink hover:border-navy/50'">
                    <x-ikon :nama="$j->ikon()" class="size-4" /> {{ $j->label() }}
                </button>
            @endforeach
        </div>
    @endif
    @error('jenis')<p class="error-text mb-2">{{ $message }}</p>@enderror
 
    @if (in_array('dokumen', $diterima, true))
        <template x-if="jenis === 'dokumen'">
            <div>
                <x-input-file name="berkas" :accept="$dokumen->map(fn ($e) => '.'.$e)->implode(',')" :maks-mb="$maksMb" required label="Berkas dokumen"
                    :bantuan="'Format: '.$dokumen->implode(', ').'. Maksimal '.$maksMb.' MB.'" />
            </div>
        </template>
    @endif
 
    @if (in_array('foto', $diterima, true))
        <template x-if="jenis === 'foto'">
            <div>
                <x-input-file name="berkas" :accept="$foto->map(fn ($e) => '.'.$e)->implode(',')" :maks-mb="$maksMb" required label="Foto jawaban"
                    :bantuan="'Format: '.$foto->implode(', ').'. Maksimal '.$maksMb.' MB. Pastikan tulisan terbaca jelas.'" />
            </div>
        </template>
    @endif
 
    @if (in_array('tautan', $diterima, true))
        <template x-if="jenis === 'tautan'">
            <div>
                <x-field type="url" name="tautan" label="Tautan jawaban" :value="old('tautan', $pengumpulan?->tautan ?? null)" placeholder="https://drive.google.com/..." maxlength="500" required
                    hint="Awali dengan http:// atau https://. Pastikan akses berbagi sudah dibuka agar gurumu bisa melihatnya." />
            </div>
        </template>
    @endif
 
    @if (in_array('teks', $diterima, true))
        <template x-if="jenis === 'teks'">
            <div x-data="{ isi: @js(old('teks', $pengumpulan?->teks ?? '')) }">
                <x-field type="textarea" name="teks" label="Jawaban teks" x-model="isi" rows="8" maxlength="{{ $maksTeks }}" required
                    placeholder="Tulis jawabanmu di sini..." />
                <p class="mt-1 text-right text-xs text-muted"><span x-text="isi.length"></span> / {{ number_format($maksTeks, 0, ',', '.') }} karakter</p>
            </div>
        </template>
    @endif
</div>
