@props(['status'])
@php $variant = match ($status) { 'Selesai' => 'success', 'Diproses' => 'warning', 'Ditolak' => 'danger', default => 'info' }; @endphp
<x-status-pill :variant="$variant">{{ $status }}</x-status-pill>
