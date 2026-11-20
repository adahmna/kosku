@props(['priority'])
@php $variant = $priority === 'Tinggi' ? 'danger' : ($priority === 'Sedang' ? 'warning' : 'success'); @endphp
<x-status-pill :variant="$variant">{{ $priority }}</x-status-pill>
