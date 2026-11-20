@props(['status'])
@php $variant = $status === 'Tersedia' ? 'success' : ($status === 'Terisi' ? 'info' : 'warning'); @endphp
<x-status-pill :variant="$variant">{{ $status }}</x-status-pill>
