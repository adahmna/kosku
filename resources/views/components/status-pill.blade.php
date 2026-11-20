@props(['variant' => 'default'])

@php
    $variants = [
        'success' => 'bg-success/15 text-success border-success/30',
        'warning' => 'bg-warning/15 text-warning-foreground border-warning/40',
        'danger' => 'bg-destructive/15 text-destructive border-destructive/30',
        'info' => 'bg-info/15 text-info border-info/30',
        'default' => 'bg-muted text-muted-foreground border-border',
        'accent' => 'bg-accent/20 text-accent-foreground border-accent/40',
    ];
    $dots = [
        'success' => 'bg-success',
        'warning' => 'bg-warning',
        'danger' => 'bg-destructive',
        'info' => 'bg-info',
        'default' => 'bg-muted-foreground',
        'accent' => 'bg-accent',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium whitespace-nowrap '.($variants[$variant] ?? $variants['default'])]) }}>
    <span class="h-1.5 w-1.5 rounded-full {{ $dots[$variant] ?? $dots['default'] }}"></span>
    {{ $slot }}
</span>
