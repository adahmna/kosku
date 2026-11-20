@props(['status'])
@php $variant = $status === 'Lunas' ? 'success' : ($status === 'Terlambat' ? 'danger' : 'warning'); @endphp
<x-status-pill :variant="$variant">{{ $status }}</x-status-pill>
