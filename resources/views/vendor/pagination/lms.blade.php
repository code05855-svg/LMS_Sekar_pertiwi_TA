@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Halaman" class="flex flex-wrap items-center justify-between gap-3 pt-4">
        <p class="text-sm text-muted">
            Menampilkan <span class="font-semibold text-ink">{{ $paginator->firstItem() }}</span>–<span class="font-semibold text-ink">{{ $paginator->lastItem() }}</span>
            dari <span class="font-semibold text-ink">{{ $paginator->total() }}</span>
        </p>

        <ul class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <li><span class="btn-icon cursor-not-allowed opacity-40" aria-disabled="true"><x-ikon nama="kiri" class="size-4" /></span></li>
            @else
                <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn-icon" aria-label="Sebelumnya"><x-ikon nama="kiri" class="size-4" /></a></li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="px-2 text-muted">…</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $halaman => $url)
                        <li>
                            @if ($halaman == $paginator->currentPage())
                                <span aria-current="page" class="grid size-9 place-items-center rounded-lg bg-navy text-sm font-semibold text-white">{{ $halaman }}</span>
                            @else
                                <a href="{{ $url }}" class="grid size-9 place-items-center rounded-lg text-sm font-semibold text-muted transition hover:bg-info-soft hover:text-navy" aria-label="Halaman {{ $halaman }}">{{ $halaman }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li><a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn-icon" aria-label="Berikutnya"><x-ikon nama="kanan" class="size-4" /></a></li>
            @else
                <li><span class="btn-icon cursor-not-allowed opacity-40" aria-disabled="true"><x-ikon nama="kanan" class="size-4" /></span></li>
            @endif
        </ul>
    </nav>
@endif
