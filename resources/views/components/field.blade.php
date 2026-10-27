{{--
    Kolom formulir: label + input + pesan galat.
    <x-field name="judul" label="Judul" :value="old('judul', $x->judul)" required />
    type: text|email|password|number|date|datetime-local|textarea|select (isi select lewat slot)
--}}
@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'hint' => null,
    'required' => false,
    'placeholder' => null,
])

@php
    $id = $attributes->get('id', 'f-'.str_replace(['[', ']', '.'], ['-', '', '-'], $name));
    $galat = $errors->first($name);
    $kelasInput = 'input'.($galat ? ' input-error' : '');
    $atribut = $attributes->except(['id', 'class'])->merge([
        'name' => $name,
        'id' => $id,
        'placeholder' => $placeholder,
        'aria-invalid' => $galat ? 'true' : null,
        'aria-describedby' => $galat ? "{$id}-galat" : ($hint ? "{$id}-hint" : null),
    ]);
@endphp

<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="label">
            {{ $label }}@if ($required)<span class="text-danger" aria-hidden="true"> *</span>@endif
        </label>
    @endif

    @if ($type === 'textarea')
        <textarea {{ $atribut->merge(['class' => $kelasInput]) }} @required($required)>{{ $value }}</textarea>
    @elseif ($type === 'select')
        <select {{ $atribut->merge(['class' => $kelasInput]) }} @required($required)>{{ $slot }}</select>
    @else
        <input type="{{ $type }}" value="{{ $type === 'password' ? '' : $value }}" {{ $atribut->merge(['class' => $kelasInput]) }} @required($required)>
    @endif

    @if ($galat)
        <p id="{{ $id }}-galat" class="error-text">{{ $galat }}</p>
    @elseif ($hint)
        <p id="{{ $id }}-hint" class="hint">{{ $hint }}</p>
    @endif
</div>
