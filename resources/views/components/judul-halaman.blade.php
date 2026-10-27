{{-- Judul halaman + deskripsi; isi slot = tombol aksi di kanan. --}}
@props(['judul', 'deskripsi' => null, 'kembali' => null, 'label' => 'Kembali'])

<div class="mb-6">
    @if ($kembali)
        <a href="{{ $kembali }}" class="mb-3 inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-navy">
            <x-ikon nama="kiri" class="size-4" /> {{ $label }}
        </a>
    @endif
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold tracking-tight text-ink sm:text-[25px]">{{ $judul }}</h1>
            @if ($deskripsi)
                <p class="mt-1 text-sm text-muted">{{ $deskripsi }}</p>
            @endif
        </div>
        @if (! $slot->isEmpty())
            <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
        @endif
    </div>
</div>
