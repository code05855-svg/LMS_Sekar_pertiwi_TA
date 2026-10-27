@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Halaman" class="flex items-center justify-between gap-3 pt-4">
        @if ($paginator->onFirstPage())
            <span class="btn-secondary btn-sm cursor-not-allowed opacity-50">Sebelumnya</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn-secondary btn-sm">Sebelumnya</a>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn-secondary btn-sm">Berikutnya</a>
        @else
            <span class="btn-secondary btn-sm cursor-not-allowed opacity-50">Berikutnya</span>
        @endif
    </nav>
@endif
