@php
    $user = auth()->user();
    $roleLabels = ['pemilik' => 'Pemilik', 'penghuni' => 'Penghuni'];
    $roleIcons = ['pemilik' => 'crown', 'penghuni' => 'user'];

    $ownerNav = [
        ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'layout-dashboard', 'active' => 'dashboard'],
        ['route' => 'rooms.index', 'label' => 'Manajemen Kamar', 'icon' => 'door-closed', 'active' => 'rooms.*'],
        ['route' => 'tenants.index', 'label' => 'Manajemen Penghuni', 'icon' => 'users', 'active' => 'tenants.*'],
        ['route' => 'bills.index', 'label' => 'Manajemen Tagihan', 'icon' => 'wallet', 'active' => 'bills.*'],
        ['route' => 'complaints.index', 'label' => 'Manajemen Pengaduan', 'icon' => 'wrench', 'active' => 'complaints.*'],
        ['route' => 'reports.index', 'label' => 'Laporan Keuangan', 'icon' => 'bar-chart-3', 'active' => 'reports.*', 'ownerOnly' => true],
        ['route' => 'settings.index', 'label' => 'Pengaturan', 'icon' => 'settings', 'active' => 'settings.*', 'ownerOnly' => true],
    ];

    $penghuniNav = [
        ['route' => 'dashboard', 'label' => 'Dashboard Saya', 'icon' => 'layout-dashboard', 'active' => 'dashboard'],
        ['route' => 'my.bills.index', 'label' => 'Tagihan Saya', 'icon' => 'credit-card', 'active' => 'my.bills.*'],
        ['route' => 'my.complaints.index', 'label' => 'Pengaduan Saya', 'icon' => 'megaphone', 'active' => 'my.complaints.*'],
        ['route' => 'my.profile.index', 'label' => 'Profil Saya', 'icon' => 'user-circle', 'active' => 'my.profile.*'],
    ];

    $items = $user->isPenghuni()
        ? $penghuniNav
        : collect($ownerNav)->reject(fn ($item) => ($item['ownerOnly'] ?? false) && ! $user->isOwner())->values()->all();

    $toasts = [];
    if (session('success')) $toasts[] = ['type' => 'success', 'message' => session('success')];
    if (session('error')) $toasts[] = ['type' => 'error', 'message' => session('error')];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'KosKu — Sistem Manajemen Kos')</title>
    <meta name="description" content="Kelola kos Anda dengan mudah dan efisien — kamar, penghuni, tagihan otomatis, dan pengaduan dalam satu sistem.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-background">
<div x-data="{ sidebarOpen: false }" class="flex min-h-screen w-full bg-background">
    <aside
        class="fixed inset-y-0 left-0 z-40 w-64 transform bg-sidebar text-sidebar-foreground transition-transform duration-200 lg:relative lg:translate-x-0"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    >
        <div class="flex h-16 items-center gap-2 border-b border-sidebar-border px-5">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-accent text-accent-foreground">
                <i data-lucide="home" class="h-5 w-5"></i>
            </div>
            <div>
                <div class="text-lg font-bold leading-tight">KosKu</div>
                <div class="text-[10px] uppercase tracking-wider text-sidebar-foreground/60">Manajemen Kos</div>
            </div>
        </div>
        <nav class="flex flex-col gap-1 p-3">
            @foreach ($items as $item)
                <a href="{{ route($item['route']) }}"
                   class="sidebar-link {{ request()->routeIs($item['active']) ? 'sidebar-link-active' : '' }}">
                    <i data-lucide="{{ $item['icon'] }}" class="h-4 w-4 shrink-0"></i>
                    <span class="truncate">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
        <div class="absolute bottom-0 left-0 right-0 hidden border-t border-sidebar-border p-4 lg:block">
            <div class="rounded-lg bg-sidebar-accent/50 p-3 text-xs text-sidebar-foreground/70">
                <div class="font-semibold text-sidebar-foreground">KosKu v1.0</div>
                <div class="mt-0.5">Sistem Manajemen Indekos</div>
            </div>
        </div>
    </aside>

    <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-30 bg-black/50 lg:hidden" @click="sidebarOpen = false"></div>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-4 border-b border-border bg-card/80 px-4 backdrop-blur lg:px-8">
            <div class="flex items-center gap-3">
                <button type="button" class="btn btn-ghost btn-icon lg:hidden" @click="sidebarOpen = !sidebarOpen">
                    <i data-lucide="menu" class="h-5 w-5" x-show="!sidebarOpen"></i>
                    <i data-lucide="x" class="h-5 w-5" x-show="sidebarOpen" x-cloak></i>
                </button>
                <div class="hidden text-sm text-muted-foreground sm:block">Selamat datang kembali 👋</div>
            </div>
            <div class="flex items-center gap-2">
                <div class="flex items-center gap-2 rounded-full border border-border bg-card px-3 py-1.5">
                    <div class="flex h-7 w-7 items-center justify-center rounded-full bg-primary text-primary-foreground">
                        <i data-lucide="{{ $roleIcons[$user->role] ?? 'user' }}" class="h-3.5 w-3.5"></i>
                    </div>
                    <span class="text-sm font-medium">{{ $roleLabels[$user->role] ?? ucfirst($user->role) }}</span>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline btn-sm gap-1.5">
                        <i data-lucide="log-out" class="h-3.5 w-3.5"></i>
                        <span class="hidden sm:inline">Keluar</span>
                    </button>
                </form>
            </div>
        </header>

        <main class="flex-1 p-4 lg:p-8">
            @if ($errors->any())
                <div class="mb-5 rounded-lg border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">
                    <div class="mb-1 flex items-center gap-2 font-semibold">
                        <i data-lucide="alert-circle" class="h-4 w-4"></i>
                        Periksa kembali data Anda
                    </div>
                    <ul class="list-inside list-disc space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <div x-data="confirmDialog" @confirm-delete.window="ask($event.detail)" x-cloak>
        <div x-show="open" class="modal-backdrop" @click="open = false"></div>
        <div x-show="open" class="modal-panel max-w-md" x-transition>
            <div class="mb-2 flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold" x-text="title"></h2>
                    <p class="mt-1 text-sm text-muted-foreground" x-text="message"></p>
                </div>
                <button type="button" class="btn btn-ghost btn-icon -mr-2 -mt-2" @click="open = false">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>
            <form :action="action" method="POST" @submit="open = false" class="mt-5 flex justify-end gap-2">
                @csrf
                <input type="hidden" name="_method" :value="method">
                <button type="button" class="btn btn-outline" @click="open = false">Batal</button>
                <button type="submit" class="btn" :class="danger ? 'btn-destructive' : 'btn-primary'" x-text="confirmLabel"></button>
            </form>
        </div>
    </div>

    <div class="fixed right-4 top-4 z-[70] flex w-[calc(100%-2rem)] max-w-sm flex-col gap-2">
        @foreach ($toasts as $toast)
            <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4500)" x-show="show" x-transition
                 class="flex items-start gap-3 rounded-lg border p-4 shadow-lg {{ $toast['type'] === 'success' ? 'border-success/30 bg-card text-foreground' : 'border-destructive/30 bg-card text-foreground' }}">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $toast['type'] === 'success' ? 'bg-success/15 text-success' : 'bg-destructive/15 text-destructive' }}">
                    <i data-lucide="{{ $toast['type'] === 'success' ? 'check-circle-2' : 'alert-circle' }}" class="h-4 w-4"></i>
                </div>
                <div class="flex-1 text-sm">{{ $toast['message'] }}</div>
                <button type="button" class="text-muted-foreground hover:text-foreground" @click="show = false">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>
        @endforeach
    </div>
</div>
@stack('scripts')
</body>
</html>
