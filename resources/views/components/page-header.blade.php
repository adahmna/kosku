@props(['title', 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between']) }}>
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 text-sm text-muted-foreground">{{ $subtitle }}</p>
        @endif
    </div>
    @if (trim($slot))
        <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
    @endif
</div>
