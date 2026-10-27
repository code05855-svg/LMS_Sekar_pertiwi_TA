{{-- Keadaan kosong: ikon, judul, penjelasan, dan (slot) tombol aksi. --}}
@props(['judul', 'deskripsi' => null, 'ikon' => 'dokumen'])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center px-4 py-10 text-center']) }}>
    <span class="grid size-14 place-items-center rounded-2xl bg-info-soft text-navy">
        <x-ikon :nama="$ikon" class="size-7" />
    </span>
    <p class="mt-4 text-base font-semibold text-ink">{{ $judul }}</p>
    @if ($deskripsi)
        <p class="mt-1 max-w-md text-sm text-muted">{{ $deskripsi }}</p>
    @endif
    @if (! $slot->isEmpty())
        <div class="mt-4 flex flex-wrap justify-center gap-2">{{ $slot }}</div>
    @endif
</div>
