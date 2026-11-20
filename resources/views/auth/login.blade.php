<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — KosKu</title>
    <meta name="description" content="Masuk ke sistem manajemen kos KosKu.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="relative flex min-h-screen items-center justify-center bg-gradient-to-br from-primary via-primary to-[oklch(0.27_0.05_254)] p-4">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,oklch(0.78_0.16_75/0.15),transparent_50%)]"></div>
    <div class="relative z-10 w-full max-w-md">
        <div class="mb-8 text-center text-primary-foreground">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-accent text-accent-foreground shadow-lg shadow-accent/30">
                <i data-lucide="home" class="h-8 w-8"></i>
            </div>
            <h1 class="text-3xl font-bold tracking-tight">KosKu</h1>
            <p class="mt-2 text-sm text-primary-foreground/80">Kelola kos Anda dengan mudah dan efisien</p>
        </div>

        <div class="card border-0 shadow-2xl">
            <div class="p-6 sm:p-8">
                @if ($errors->any())
                    <div class="mb-4 rounded-lg border border-destructive/30 bg-destructive/10 p-3 text-sm text-destructive">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form action="{{ route('login.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="email" class="label">Email</label>
                        <div class="relative">
                            <i data-lucide="mail" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"></i>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" class="input pl-9" placeholder="nama@email.com" required autofocus>
                        </div>
                    </div>
                    <div>
                        <label for="password" class="label">Password</label>
                        <div class="relative">
                            <i data-lucide="lock" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"></i>
                            <input id="password" type="password" name="password" class="input pl-9" placeholder="••••••••" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-full">Masuk</button>
                </form>

                <p class="mt-6 text-center text-xs text-muted-foreground">
                    Penghuni menggunakan email yang terdaftar pada data penghuni. Hubungi pemilik kos jika belum memiliki akun.
                </p>
            </div>
        </div>

        <p class="mt-6 text-center text-xs text-primary-foreground/60">© {{ date('Y') }} KosKu. Sistem manajemen indekos modern.</p>
    </div>
</div>
</body>
</html>
