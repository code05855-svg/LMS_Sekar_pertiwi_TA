{{-- Daftar lampiran tugas dari guru (dokumen, foto, tautan). <x-daftar-lampiran :tugas="$tugas" /> --}}
@props(['tugas'])
 
@if ($tugas->lampiran->isNotEmpty())
    <div {{ $attributes }}>
        <h2 class="text-lg font-bold text-ink">Lampiran dari guru</h2>
        <ul class="mt-3 space-y-2">
            @foreach ($tugas->lampiran as $l)
                @php
                    $j = $l->jenis;
                    $aman = $j === \App\Enums\JenisJawaban::Tautan && preg_match('#^https?://#i', (string) $l->tautan);
                @endphp
                <li class="flex flex-wrap items-center gap-3 rounded-xl border border-line bg-white p-3">
                    <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-info-soft text-navy"><x-ikon :nama="$j->ikon()" class="size-5" /></span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-ink">{{ $l->namaTampil() }}</p>
                        <p class="truncate text-xs text-muted">
                            @if ($j === \App\Enums\JenisJawaban::Tautan){{ $l->tautan }}@else{{ $j->label() }}@if ($l->ukuran) · {{ \App\Support\Format::ukuran($l->ukuran) }}@endif @endif
                        </p>
                    </div>
                    <div class="flex gap-2">
                        @if ($aman)
                            <a href="{{ $l->tautan }}" target="_blank" rel="noopener noreferrer nofollow" class="btn-secondary btn-sm"><x-ikon nama="tautan" class="size-4" /> Buka</a>
                        @elseif ($j !== \App\Enums\JenisJawaban::Tautan)
                            @if ($l->bisaDilihatLangsung())
                                <a href="{{ route('lampiran-tugas.show', [$l, 'lihat' => 1]) }}" target="_blank" rel="noopener" class="btn-secondary btn-sm"><x-ikon nama="lihat" class="size-4" /> Buka</a>
                            @endif
                            <a href="{{ route('lampiran-tugas.show', $l) }}" class="btn-secondary btn-sm"><x-ikon nama="unduh" class="size-4" /> Unduh</a>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
@endif
