{{-- Lingkaran inisial nama. --}}
@props(['nama', 'ukuran' => 'size-10 text-sm'])

@php
    $kata = preg_split('/\s+/u', trim($nama), -1, PREG_SPLIT_NO_EMPTY);
    $inisial = mb_strtoupper(mb_substr($kata[0] ?? '?', 0, 1).(count($kata) > 1 ? mb_substr(end($kata), 0, 1) : mb_substr($kata[0] ?? '', 1, 1)));
@endphp

<span {{ $attributes->merge(['class' => "grid shrink-0 place-items-center rounded-full bg-gold font-bold text-navy {$ukuran}"]) }} aria-hidden="true">{{ $inisial }}</span>
