# KosKu

Aplikasi manajemen kos berbasis Laravel. Pemilik kos bisa mengelola kamar, penghuni, tagihan, keluhan, laporan, dan pengaturan. Penghuni bisa melihat tagihan, mengirim keluhan, dan mengelola profil mereka sendiri.

## Fitur

**Pemilik**
- Dashboard ringkasan kos
- Manajemen kamar
- Manajemen penghuni
- Manajemen tagihan
- Manajemen keluhan
- Laporan
- Pengaturan

**Penghuni**
- Dashboard penghuni
- Lihat tagihan saya
- Kirim dan pantau keluhan
- Kelola profil

## Teknologi

- PHP dan Laravel
- MySQL / MariaDB
- Vite (aset frontend) dan Blade
- PHPUnit untuk pengujian

## Persyaratan

- PHP 8.2 atau lebih baru
- Composer
- Node.js dan npm
- MySQL atau MariaDB

## Cara Menjalankan

1. Clone repository

   ```bash
   git clone https://github.com/adahmna/kosku.git
   cd kosku
   ```

2. Install dependensi

   ```bash
   composer install
   npm install
   ```

3. Siapkan file environment

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   Di Windows PowerShell, gunakan `Copy-Item .env.example .env`.

4. Buat database kosong (misalnya `kosku`), lalu atur koneksinya di file `.env`:

   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=kosku
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. Jalankan migrasi dan seeder

   ```bash
   php artisan migrate --seed
   ```

6. Jalankan aplikasi

   ```bash
   npm run build
   php artisan serve
   ```

   Untuk pengembangan, jalankan `npm run dev` di terminal terpisah.

7. Buka http://localhost:8000 di browser.

## Menjalankan Tes

```bash
php artisan test
```

## Catatan

- File `.env` dan data database lokal tidak disertakan di repository ini. Buat sendiri sesuai langkah di atas.
- Akun awal (login) bergantung pada isi `database/seeders/DatabaseSeeder.php`.
