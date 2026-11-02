{{--
    Pratinjau jawaban siswa menurut jenisnya: dokumen, foto, tautan, atau teks.
    Dipakai halaman penilaian guru (lengkap) dan halaman tugas siswa (:kompak="true").
 
    <x-pratinjau-jawaban :pengumpulan="$p" :url-lihat="route(...)" :url-unduh="route(...)" :teks-pratinjau="$teks" />
--}}
@props(['pengumpulan', 'urlLihat' => null, 'urlUnduh' => null, 'teksPratinjau' => null, 'kompak' => false])
 
@php
    $j = $pengumpulan->jenis;
    $langsung = $pengumpulan->bisaDilihatLangsung();
    $tautanAman = $j === \App\Enums\JenisJawaban::Tautan && preg_match('#^https?://#i', (string) $pengumpulan->tautan);
@endphp
 
<div>
    <div class="flex flex-wrap items-center gap-3 rounded-xl bg-canvas p-4">
        <span class="grid {{ $kompak ? 'size-11' : 'size-12' }} shrink-0 place-items-center rounded-xl bg-info-soft text-navy"><x-ikon :nama="$j->ikon()" class="size-5" /></span>
        {{-- Mode kompak (kolom sempit): judul boleh membungkus dan lencana turun ke baris berikutnya. --}}
        <div class="min-w-0 flex-1 {{ $kompak ? 'basis-44' : '' }}">
            <p class="{{ $kompak ? 'wrap-break-word' : 'truncate' }} font-semibold text-ink">{{ $pengumpulan->ringkasan() }}</p>
            <p class="text-xs text-muted">
                {{ $j->label() }}@if ($pengumpulan->berupaBerkas() && $pengumpulan->ukuran) · {{ \App\Support\Format::ukuran($pengumpulan->ukuran) }}@endif · {{ $pengumpulan->waktu_kumpul->translatedFormat('d M Y, H:i') }}
            </p>
        </div>
        <x-badge-status :status="$pengumpulan->status" />
    </div>
 
    {{-- Tombol --}}
    @if ($pengumpulan->berupaBerkas() || $tautanAman)
        <div class="mt-4 flex flex-wrap gap-2">
            @if ($tautanAman)
                <a href="{{ $pengumpulan->tautan }}" target="_blank" rel="noopener noreferrer nofollow" class="btn-primary btn-sm"><x-ikon nama="tautan" class="size-4" /> Buka tautan</a>
            @else
                @if ($langsung && $urlLihat)
                    <a href="{{ $urlLihat }}" target="_blank" rel="noopener" class="btn-secondary btn-sm"><x-ikon nama="lihat" class="size-4" /> Buka di tab baru</a>
                @endif
                @if ($urlUnduh)
                    <a href="{{ $urlUnduh }}" class="{{ $kompak ? 'btn-secondary' : 'btn-primary' }} btn-sm"><x-ikon nama="unduh" class="size-4" /> Unduh</a>
                @endif
            @endif
        </div>
    @endif
 
    {{-- Isi --}}
    @switch($j)
        @case(\App\Enums\JenisJawaban::Foto)
            @if ($langsung && $urlLihat)
                <a href="{{ $urlLihat }}" target="_blank" rel="noopener" class="mt-4 block">
                    <img src="{{ $urlLihat }}" alt="Foto jawaban" class="{{ $kompak ? 'max-h-60' : 'max-h-128' }} w-full rounded-xl border border-line bg-canvas object-contain" loading="lazy">
                </a>
            @endif
            @break
 
        @case(\App\Enums\JenisJawaban::Dokumen)
            @if ($kompak)
                @break
            @endif
            @if ($pengumpulan->mime === 'application/pdf' && $urlLihat)
                <iframe src="{{ $urlLihat }}" title="Pratinjau {{ $pengumpulan->nama_asli }}" class="mt-4 h-128 w-full rounded-xl border border-line bg-canvas"></iframe>
            @elseif ($teksPratinjau)
                <div class="mt-4">
                    <p class="mb-2 text-xs text-muted">Pratinjau teks (tanpa gambar dan tata letak). Unduh untuk melihat berkas aslinya.</p>
                    <div class="max-h-128 overflow-auto whitespace-pre-wrap wrap-break-word rounded-xl border border-line bg-white p-4 text-sm leading-relaxed text-ink">{{ $teksPratinjau['teks'] }}</div>
                    @if ($teksPratinjau['dipotong'])
                        <p class="mt-2 text-xs text-muted">Pratinjau dipotong pada 20.000 karakter pertama.</p>
                    @endif
                </div>
            @else
                <p class="mt-4 text-sm text-muted">Pratinjau tidak tersedia untuk jenis berkas ini (PDF, DOCX, dan TXT bisa dipratinjau). Unduh untuk memeriksanya.</p>
            @endif
            @break
 
        @case(\App\Enums\JenisJawaban::Tautan)
            <div class="mt-4 rounded-xl border border-line bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-muted">Alamat tautan</p>
                <p class="mt-1 break-all text-sm text-ink">{{ $pengumpulan->tautan }}</p>
                @unless ($kompak)
                    <p class="mt-3 text-xs text-muted">Tautan dikirim siswa dan dibuka di tab baru. Pastikan akses berbagi (mis. Google Drive) sudah dibuka, dan berhati-hati terhadap situs yang tidak dikenal.</p>
                @endunless
            </div>
            @break
 
        @case(\App\Enums\JenisJawaban::Teks)
            <div class="mt-4">
                <div class="{{ $kompak ? 'max-h-60' : 'max-h-128' }} overflow-auto whitespace-pre-wrap wrap-break-word rounded-xl border border-line bg-white p-4 text-sm leading-relaxed text-ink">{{ $pengumpulan->teks }}</div>
                <p class="mt-2 text-xs text-muted">{{ $pengumpulan->jumlahKata() }} kata · {{ mb_strlen((string) $pengumpulan->teks) }} karakter</p>
            </div>
            @break
    @endswitch
</div>
