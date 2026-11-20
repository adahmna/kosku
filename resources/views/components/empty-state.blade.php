@props(['icon' => 'inbox', 'title', 'description' => null])

<div class="flex flex-col items-center justify-center gap-3 py-16 text-center">
    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-muted">
        <i data-lucide="{{ $icon }}" class="h-7 w-7 text-muted-foreground"></i>
    </div>
    <div>
        <div class="font-medium">{{ $title }}</div>
        @if ($description)
            <div class="mt-1 text-sm text-muted-foreground">{{ $description }}</div>
        @endif
    </div>
    @if (trim($slot))
        <div class="mt-2">{{ $slot }}</div>
    @endif
</div>
