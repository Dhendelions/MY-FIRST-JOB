# Dadhen Mandiri — Platform Kolaborasi Manajemen Operasional & Pembukuan Spreadsheet Usaha Budidaya Perikanan Indonesia

> **Arsitektur:** Laravel 11 + Blade Components + Livewire 3 + Alpine.js + Tailwind CSS.  
> **Komoditas:** Tambak Lele Bioflok, Nila Merah RAS, Gurami Soang, Udang Vaname Super Intensif, Patin, & Polikultur Tambak Indonesia.

---

## 🌟 Fitur Utama

1. **Aplikasi Shell Berbasis Dark Navy (`#0A192F`)**:
   - Sidebar navigasi modern dengan brand icon maritim Dadhen Mandiri.
   - Switcher kelompok tambak real-time & tombol cepat `+ Buat Grup Baru`.
   - Topbar dengan breadcrumb kelompok aktif, badge berdenyut **Operasional Aktif**, dan dropdown notifikasi.

2. **5 Kategori Tab Perikanan Mandiri**:
   - **Tab 1: Produksi** — Tonase panen, sampling bobot (ABW), biomassa, dan FCR kolam.
   - **Tab 2: Bahan Pendukung** — Pakan pelet harian, benih ikan, probiotik & molase bioflok.
   - **Tab 3: Packaging** — Kantong plastik oksigen, es kristal/balok, styrofoam & lakban panen.
   - **Tab 4: Payroll** — Gaji teknisi kolam, upah harian sortasi panen & uang lembur.
   - **Tab 5: Air & Listrik** — Biaya token PLN, solar genset blower nano-bubble & uji pH/DO air.
   - *Dukungan penambahan lembar kustom tanpa batas (+ Sheet).*

3. **Lembar Kerja Spreadsheet Ala Microsoft Excel (Livewire 3 + Alpine.js)**:
   - **Tanpa Template Bawaan Kaku**: Grid awal bersih dengan kolom alfabet (A, B, C, D, E, F) tanpa judul terpaksa dan baris siap diisi.
   - **Toolbar Produktivitas**: Tambah Baris, Tambah Kolom dinamis (G, H, I...), Ekspor CSV kompatibel dengan Microsoft Excel (UTF-8 BOM), Cetak Lembar Kerja (`window.print()`), filter pencarian sel, dan statistik sel.
   - **Formula Bar Sinkron**: Menampilkan koordinat aktif (misal `A1`, `B4`), ikon `fx`, dan sinkronisasi input dua arah.
   - **Navigasi Keyboard Penuh**:
     - `Enter` / `Shift+Enter`: Pindah baris bawah / atas (*otomatis tambah baris baru jika di baris paling bawah*).
     - `Tab` / `Shift+Tab`: Pindah kolom kanan / kiri.
     - Tombol Panah (Arrow keys) untuk perpindahan sel.
   - **Penataan Konten Otomatis**: Angka atau format mata uang (`Rp`) otomatis rata kanan dengan font monospace `tabular-nums`; teks biasa rata kiri.
   - **Dukungan Paste Multi-Sel**: Mampu menerima teks TSV (Tab-Separated) hasil copy dari MS Excel atau Google Sheets dan langsung mengisi petak sel lintas baris dan kolom.
   - **Bilah Ringkasan Finansial Bawah**: Menghitung secara otomatis **Total (SUM)** dan **Rata-rata (AVERAGE)** dari sel numerik/rupiah, dengan indikator status *Tersimpan Otomatis*.

4. **Katalog & Kode Undangan Kelompok**:
   - Setiap kelompok memiliki kode unik (contoh: `LELE-MKMR-88`, `NILA-BRKH-42`).
   - Tombol 1-klik salin kode undangan untuk dibagikan ke teknisi kolam atau staff keuangan.
   - Modal dialog lengkap: Buat Grup, Gabung Grup, Tambah Mitra Pertemanan, Tambah Anggota, Pengaturan Kelompok, dan Konfirmasi Hapus Kolom Berisi Data.

## XLSX Template Support

The `Okt Analisa.xlsx` template is imported through PhpSpreadsheet and stored as regular editable worksheets. Its verified layout is:

| Worksheet | Used dimensions | Merged ranges | Freeze pane |
|---|---:|---:|---|
| Analisa | 29 x 21 | 32 | C6 |
| Cost | 26 x 34 | 47 | C8 |
| Margin | 33 x 23 | 27 | C5 |
| pendukung | 11 x 3 | 0 | None |
| Rank | 16 x 6 | 0 | None |

The importer stores formulas separately from calculated values, cell styles, row heights, column widths, merged ranges, and freeze-pane settings. XLSX export writes every worksheet in the workspace and preserves supported formulas, styles, and layout metadata. Uploaded workbook worksheets are appended; duplicate worksheet names receive a suffix, and internal sheet references are remapped.

Formula calculation is workbook-wide. In the supplied template, 517 formulas are internal and one formula (`Rank!C6`) references the unavailable external workbook `[1]Payroll`. Its formula and cached value are retained and marked as external; it is not recalculated without that source. PhpSpreadsheet recalculation of the template also produces 13 existing formula errors (including `#VALUE!` and `#DIV/0!`); the application preserves these results rather than changing business formulas.

PhpSpreadsheet 5.10 is MIT-licensed. The PHP GD extension is a declared package requirement and must be enabled for normal Composer platform checks; the current development runtime has GD disabled. XLSX files are limited to 20 worksheets and 100,000 grid positions per worksheet for import.

Cell edits are synchronized to other open sessions with a five-second Livewire poll. Cell versions detect stale writes; conflicts are surfaced and use last-write-wins. There is no active-user presence indicator or durable offline edit queue yet. The metadata and version migrations must be applied with `php artisan migrate` before deploying these changes.

---

## 📂 Struktur Direktori Proyek

```
JOB 1/
├── app/
│   ├── Http/Controllers/
│   │   ├── AuthController.php          # Login, Register, Split-screen & Quick Fill Demo Login
│   │   ├── DashboardController.php     # Ringkasan, KPI cards, Group Catalog & Friendships
│   │   ├── GroupController.php         # Ruang Kerja Tambak, Member Management & Custom Sheets
│   │   └── SpreadsheetController.php   # Ekspor CSV Excel, Simpan Sel, Tambah/Hapus Kolom & Baris
│   ├── Livewire/
│   │   └── SpreadsheetWorksheet.php    # Komponen Utama Livewire 3 Spreadsheet Excel-like Grid
│   └── Models/
│       ├── User.php                    # Pengguna & Aksesoris Avatar
│       ├── FisheriesGroup.php          # Kelompok Tambak & Auto-Init 5 Kategori Sheets
│       ├── GroupMember.php             # Peran: Ketua Tambak, Teknisi Kolam, Staff Keuangan
│       ├── SpreadsheetSheet.php        # Lembar Kerja Spreadsheet
│       ├── SpreadsheetColumn.php       # Kolom Alfabet (A, B, C...) & Deteksi Sel Terisi
│       ├── SpreadsheetRow.php          # Nomor Baris (1, 2, 3...)
│       ├── SpreadsheetCell.php         # Nilai Sel & Helper Deteksi Numerik / Format Rp
│       ├── Friendship.php              # Mitra Rekan Petambak
│       └── Notification.php            # Notifikasi Operasional Tambak
├── database/
│   ├── migrations/                     # 9 File Migrasi Database Lengkap
│   └── seeders/
│       └── DatabaseSeeder.php          # Seeder Realistis Tambak Lele, Nila, Gurami & Udang
├── resources/
│   └── views/
│       ├── auth/
│       │   ├── login.blade.php         # Login Split-Screen dengan Tombol Quick Fill Uji Cepat
│       │   └── register.blade.php      # Register Split-Screen dengan Peran Usaha Tambak
│       ├── components/
│       │   ├── modal.blade.php         # Modal Dialog Blade Component
│       │   └── card.blade.php
│       ├── groups/
│       │   └── workspace.blade.php     # Ruang Kerja Kelompok, 5 Tab Kategori & Mount Livewire
│       ├── layouts/
│       │   ├── app.blade.php           # App Shell Utama (Sidebar Navy #0A192F & Topbar)
│       │   └── auth.blade.php          # Split-Screen Auth Shell
│       ├── livewire/
│       │   └── spreadsheet-worksheet.blade.php  # Grid Interaktif Excel-like + Alpine.js
│       └── dashboard.blade.php         # Halaman Utama Dasbor Ringkasan & 4 Kartu KPI
├── routes/
│   ├── web.php                         # Seluruh Web Route & Endpoint Terproteksi Auth
│   └── console.php
├── public/
│   ├── demo.html                       # Pratinjau Interaktif Lengkap (Dapat dibuka langsung di browser)
│   └── index.php                       # Web Server Entry Point
├── composer.json                       # Konfigurasi Dependency Laravel 11 & Livewire 3
├── artisan                             # CLI Executable Laravel
└── .env.example                        # Template Variabel Lingkungan
```

---

## 🚀 Panduan Instalasi & Menjalankan Aplikasi

### Opsi A: Jalankan Server Laravel 11 (Lokal)

1. Pastikan **PHP >= 8.2** dan **Composer** telah terpasang di sistem operasi Anda.
2. Buka terminal di folder proyek ini (`c:\Users\Crissencio Dheni S\Documents\JOB 1`):
   ```bash
   # Salin file konfigurasi .env
   copy .env.example .env

   # Install dependensi via composer
   composer install

   # Generate kunci enkripsi aplikasi
   php artisan key:generate

   # Buat database SQLite dan jalankan migrasi beserta data seeder
   touch database/database.sqlite
   php artisan migrate --seed

   # Jalankan server lokal
   php artisan serve
   ```
3. Buka browser di `http://127.0.0.1:8000`.

### Opsi B: Pratinjau Langsung (Instant Testing)

Anda dapat langsung membuka file pratinjau mandiri yang memuat seluruh arsitektur UI Blade + Livewire + Alpine.js tanpa memerlukan konfigurasi server lokal:
```
c:\Users\Crissencio Dheni S\Documents\JOB 1\public\demo.html
```
Cukup klik dua kali `public/demo.html` di File Explorer Anda untuk menguji seluruh spreadsheet secara offline!

---

## 🔑 Akun Demo Siap Pakai (Database Seeder)

| Peran Tambak | Email | Password | WhatsApp |
|---|---|---|---|
| **Pemilik / Pengelola Tambak** | `dheni@dadhenmandiri.id` | `password` | `+6281234567890` |
| **Teknisi Kolam Bioflok** | `budi@dadhenmandiri.id` | `password` | `+6285678901234` |
| **Staff Keuangan & Logistik** | `siti@dadhenmandiri.id` | `password` | `+6287890123456` |
| **Anggota Pembudidaya** | `rian@dadhenmandiri.id` | `password` | `+6289012345678` |

*(Di halaman login tersedia tombol **Quick Fill / Demo Login** untuk masuk dengan 1-klik tanpa perlu mengetik kredensial).*
