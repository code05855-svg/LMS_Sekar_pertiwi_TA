{{-- Kartu angka dengan garis warna di kiri (bento dashboard). warna: navy|danger|gold|success --}}
@props(['label', 'nilai', 'catatan' => null, 'warna' => 'navy', 'href' => null])

@php
    $gaya = [
        'navy' => ['border-l-navy', 'text-navy'],
        'danger' => ['border-l-danger', 'text-danger'],
        'gold' => ['border-l-gold', 'text-gold-600'],
        'success' => ['border-l-success', 'text-success'],
    ][$warna] ?? ['border-l-navy', 'text-navy'];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => "card block border-l-4 {$gaya[0]} p-5 ".($href ? 'transition hover:-translate-y-0.5 hover:shadow-(--shadow-pop)' : '')]) }}>
    <p class="text-sm text-muted">{{ $label }}</p>
    <p class="mt-1 text-4xl font-bold tracking-tight {{ $gaya[1] }}">{{ $nilai }}</p>
    @if ($catatan)
        <p class="mt-2 text-xs text-muted">{{ $catatan }}</p>
    @endif
</{{ $tag }}>
