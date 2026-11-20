@props(['icon', 'label', 'value', 'accent' => 'primary'])

@php
    $accents = [
        'primary' => 'bg-primary/10 text-primary',
        'accent' => 'bg-accent/20 text-accent-foreground',
        'success' => 'bg-success/15 text-success',
        'danger' => 'bg-destructive/15 text-destructive',
    ];
@endphp

<div class="card">
    <div class="flex items-start gap-4 p-5">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $accents[$accent] ?? $accents['primary'] }}">
            <i data-lucide="{{ $icon }}" class="h-5 w-5"></i>
        </div>
        <div class="min-w-0 flex-1">
            <div class="text-xs font-medium uppercase tracking-wider text-muted-foreground">{{ $label }}</div>
            <div class="mt-1 truncate text-2xl font-bold text-foreground">{{ $value }}</div>
        </div>
    </div>
</div>
