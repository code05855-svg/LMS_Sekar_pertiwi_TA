{{--
    Pilih berkas dengan seret-dan-lepas. Ukuran maksimum hanya peringatan
    di peramban; validasi sebenarnya di Form Request.
    <x-input-file name="berkas[]" multiple accept=".pdf" :maks-mb="20" label="Berkas PDF" />
--}}
@props([
    'name' => 'berkas',
    'label' => null,
    'accept' => null,
    'multiple' => false,
    'maksMb' => 10,
    'bantuan' => null,
    'required' => false,
])

@php
    $kunciGalat = str_replace('[]', '', $name);
    $galat = $errors->first($kunciGalat) ?: $errors->first($kunciGalat.'.*');
    $id = 'f-'.preg_replace('/[^a-z0-9]+/i', '-', $name);
@endphp

<div x-data="inputBerkas({{ (int) $maksMb }})" {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="label">{{ $label }}@if ($required)<span class="text-danger" aria-hidden="true"> *</span>@endif</label>
    @endif

    <label for="{{ $id }}"
           class="flex cursor-pointer flex-col items-center gap-1 rounded-xl border-2 border-dashed px-4 py-6 text-center transition"
           :class="seret ? 'border-navy bg-info-soft' : '{{ $galat ? 'border-danger bg-danger-soft/40' : 'border-line bg-canvas hover:border-navy/50' }}'"
           @dragover.prevent="seret = true" @dragleave.prevent="seret = false" @drop.prevent="lepas($event)">
        <x-ikon nama="unggah" class="size-6 text-navy" />
        <span class="text-sm font-semibold text-ink">Klik untuk memilih berkas, atau seret ke sini</span>
        <span class="text-xs text-muted">{{ $bantuan ?? 'Maksimal '.$maksMb.' MB'.($multiple ? ' per berkas' : '') }}</span>
        <input x-ref="input" id="{{ $id }}" type="file" name="{{ $name }}" class="sr-only"
               @if ($accept) accept="{{ $accept }}" @endif @if ($multiple) multiple @endif @required($required)
               @change="ubah($event)">
    </label>

    <ul class="mt-2 space-y-1.5" x-show="daftar.length" x-cloak>
        <template x-for="f in daftar" :key="f.nama">
            <li class="flex items-center gap-2 rounded-lg bg-info-soft px-3 py-2 text-sm text-navy">
                <x-ikon nama="klip" class="size-4" />
                <span class="min-w-0 flex-1 truncate font-medium" x-text="f.nama"></span>
                <span class="shrink-0 text-xs text-muted" x-text="f.ukuran"></span>
            </li>
        </template>
    </ul>

    <p class="error-text" x-show="galat" x-text="galat" x-cloak></p>
    @if ($galat)
        <p class="error-text">{{ $galat }}</p>
    @endif
</div>
