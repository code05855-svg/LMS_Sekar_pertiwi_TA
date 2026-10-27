{{-- Kartu putih membulat. Slot "aksi" tampil di kanan judul. --}}
@props(['judul' => null, 'deskripsi' => null, 'tanpaPadding' => false])

<section {{ $attributes->merge(['class' => 'card']) }}>
    @if ($judul || isset($aksi))
        <header class="flex flex-wrap items-start justify-between gap-3 px-5 pt-5 sm:px-6 sm:pt-6">
            <div class="min-w-0">
                @if ($judul)
                    <h2 class="text-lg font-bold tracking-tight text-ink">{{ $judul }}</h2>
                @endif
                @if ($deskripsi)
                    <p class="mt-0.5 text-sm text-muted">{{ $deskripsi }}</p>
                @endif
            </div>
            @isset($aksi)
                <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $aksi }}</div>
            @endisset
        </header>
    @endif

    <div @class(['p-5 sm:p-6' => ! $tanpaPadding, 'pt-4 sm:pt-4' => ! $tanpaPadding && ($judul || isset($aksi)), 'pt-3' => $tanpaPadding && ($judul || isset($aksi))])>
        {{ $slot }}
    </div>
</section>
