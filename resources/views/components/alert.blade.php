@props(['tipe' => 'info', 'judul' => null])

@php
    $gaya = [
        'info' => ['bg-info-soft text-navy', 'info'],
        'sukses' => ['bg-success-soft text-success', 'sukses'],
        'galat' => ['bg-danger-soft text-danger', 'peringatan'],
        'peringatan' => ['bg-gold-soft text-[#7a5800]', 'peringatan'],
    ][$tipe] ?? ['bg-info-soft text-navy', 'info'];
@endphp

<div {{ $attributes->merge(['class' => "flex gap-3 rounded-xl px-4 py-3 text-sm {$gaya[0]}"]) }} role="{{ $tipe === 'galat' ? 'alert' : 'status' }}">
    <x-ikon :nama="$gaya[1]" class="mt-0.5 size-5" />
    <div class="min-w-0 flex-1 leading-relaxed">
        @if ($judul)
            <p class="font-semibold">{{ $judul }}</p>
        @endif
        {{ $slot }}
    </div>
</div>
