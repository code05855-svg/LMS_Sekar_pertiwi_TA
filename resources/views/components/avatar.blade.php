{{--
    Foto profil bila ada, jika tidak lingkaran inisial nama.
    <x-avatar :user="$pengguna" ukuran="size-10 text-sm" />   (foto + inisial)
    <x-avatar :nama="$nama" />                                 (hanya inisial)
--}}
@props(['user' => null, 'nama' => null, 'ukuran' => 'size-10 text-sm'])
 
@php
    $nama = $user?->nama ?? $nama ?? '?';
    $foto = $user?->fotoProfilUrl();
    $kata = preg_split('/\s+/u', trim($nama), -1, PREG_SPLIT_NO_EMPTY);
    $inisial = mb_strtoupper(mb_substr($kata[0] ?? '?', 0, 1).(count($kata) > 1 ? mb_substr(end($kata), 0, 1) : mb_substr($kata[0] ?? '', 1, 1)));
@endphp
 
@if ($foto)
    <img src="{{ $foto }}" alt="" loading="lazy" decoding="async" {{ $attributes->merge(['class' => "shrink-0 rounded-full bg-canvas object-cover {$ukuran}"]) }}>
@else
    <span {{ $attributes->merge(['class' => "grid shrink-0 place-items-center rounded-full bg-gold font-bold text-navy {$ukuran}"]) }} aria-hidden="true">{{ $inisial }}</span>
@endif
