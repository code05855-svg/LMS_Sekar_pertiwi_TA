{{-- Sampul mata pelajaran: foto bila ada, jika tidak gradasi dan inisial. --}}
@props(['nama'])

@php
    $url = \App\Support\SampulMapel::url($nama);
@endphp

<div {{ $attributes->merge(['class' => 'relative overflow-hidden bg-gradient-to-br '.\App\Support\SampulMapel::gradasi($nama)]) }}>
    @if ($url)
        <img src="{{ $url }}" alt="" loading="lazy" class="absolute inset-0 size-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-t from-navy-900/55 via-transparent to-transparent"></div>
    @else
        <span class="absolute inset-0 grid place-items-center text-4xl font-extrabold tracking-wider text-white/90">{{ \App\Support\SampulMapel::inisial($nama) }}</span>
    @endif
    {{ $slot }}
</div>
