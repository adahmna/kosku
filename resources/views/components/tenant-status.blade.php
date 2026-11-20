@props(['status'])
<x-status-pill :variant="$status === 'Aktif' ? 'success' : 'default'">{{ $status }}</x-status-pill>
