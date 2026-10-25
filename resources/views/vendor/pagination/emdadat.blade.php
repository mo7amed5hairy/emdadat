@if ($paginator->hasPages())
    <div class="flex items-center gap-1">
        @if ($paginator->onFirstPage())
            <span class="inline-flex h-8 items-center gap-1 rounded-lg border border-border bg-card px-3 text-xs font-medium opacity-40"><i data-lucide="chevron-right" class="h-4 w-4"></i> السابق</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="inline-flex h-8 items-center gap-1 rounded-lg border border-border bg-card px-3 text-xs font-medium transition-colors hover:bg-accent"><i data-lucide="chevron-right" class="h-4 w-4"></i> السابق</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-1 text-muted-foreground">…</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg border border-brand bg-brand px-2 text-xs font-medium text-primary-foreground">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg border border-border bg-card px-2 text-xs font-medium transition-colors hover:bg-accent">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="inline-flex h-8 items-center gap-1 rounded-lg border border-border bg-card px-3 text-xs font-medium transition-colors hover:bg-accent">التالي <i data-lucide="chevron-left" class="h-4 w-4"></i></a>
        @else
            <span class="inline-flex h-8 items-center gap-1 rounded-lg border border-border bg-card px-3 text-xs font-medium opacity-40">التالي <i data-lucide="chevron-left" class="h-4 w-4"></i></span>
        @endif
    </div>
@endif