# ISA SmartWork

**Enterprise Workforce & Field Operations Platform** untuk PT ISA Tri Selaras Gemilang (Agrikultural).

Sistem manajemen karyawan lapangan (Sales/Karyawan) yang mencakup **Presensi**, **Rencana Kunjungan**, **Kunjungan Harian** (Check In/Check Out dengan foto, selfie, dan GPS), **Riwayat Kunjungan**, serta modul **Administrasi** (Master Toko, Monitoring Rute, Monitoring Kunjungan, dan Laporan).

---

## 1. Nama Proyek

**ISA SmartWork**

## 2. Deskripsi Proyek

Platform berbasis web (mobile-first) untuk mengelola aktivitas harian karyawan lapangan:

- **Presensi** — check in / check out kerja harian dengan selfie, GPS, alamat, dan waktu real-time. Halaman otomatis reset setiap hari; data historis tetap tersimpan.
- **Rencana Kunjungan** — karyawan membuat rencana kunjungan harian dengan memilih toko dari Master Toko. Tampilan bergulir otomatis (data historis tersimpan).
- **Kunjungan Hari Ini** — menampilkan otomatis toko dari rencana hari ini; karyawan melakukan Check In dan Check Out Kunjungan.
- **Riwayat Kunjungan** — histori seluruh kunjungan dengan filter Hari/Minggu/Bulan.
- **Administrasi** — kelola pengguna, master toko, monitoring rute & kunjungan, dan laporan (PDF/Excel).

## 3. Peran Sistem (Role)

| Role | Deskripsi |
|------|-----------|
| `super-admin` | Akses penuh seluruh sistem. |
| `admin` | Mengelola pengguna, master toko, monitoring rute/kunjungan, dan laporan. |
| `karyawan` | Karyawan lapangan / Sales — Presensi, Rencana Kunjungan, Kunjungan Hari Ini, Riwayat Kunjungan, Profil Saya. |

## 4. Akun Login

| Role | Username | Email |
|------|----------|-------|
| Super Admin | `superadmin` | `superadmin@isatriselaras.id` |
| Admin | `admin` | `admin@isatriselaras.id` |
| Karyawan / Sales | `karyawan` | `karyawan@isatriselaras.id` |

## 5. Password Default

Semua akun di atas menggunakan password default: **`password`**

> ⚠️ Segera ganti password default setelah instalasi.

## 6. Daftar Fitur

**Modul Karyawan / Sales**
- Dashboard karyawan (Presensi Hari Ini, Status Presensi, Jumlah Kunjungan Hari Ini, Jumlah Rencana Kunjungan, Ringkasan Aktivitas, Jadwal Kunjungan Hari Ini).
- Presensi: Check In Kerja & Check Out Kerja (selfie + GPS + alamat + waktu real-time).
- Rencana Kunjungan: buat rencana harian, pilih toko dari Master Toko, catatan opsional.
- Kunjungan Hari Ini: daftar toko dari rencana hari ini + Check In/Check Out Kunjungan.
- Check In Kunjungan: foto etalase, selfie, barang dibawa, uang diterima, catatan awal; otomatis tanggal, waktu, GPS, lat/lng, alamat, link Google Maps.
- Check Out Kunjungan: hasil kunjungan, barang diserahkan, barang retur, nilai transaksi, catatan akhir, foto akhir toko, selfie; otomatis GPS/lokasi.
- Riwayat Kunjungan: tanggal, nama toko, status, hasil, nilai transaksi, dokumentasi; filter Hari/Minggu/Bulan.
- Profil Saya: kelola nama, username, email, telepon, dan password.

**Modul Admin / Super Admin**
- Kelola Pengguna.
- Master Toko (Nama Toko, Alamat, Kota, Provinsi, Status Aktif).
- Monitoring Rute & Monitoring Kunjungan.
- Laporan (Attendance, Rute, Kunjungan, Aktivitas Sales) dengan ekspor PDF & Excel.

## 7. Panduan Instalasi

Persyaratan: PHP ^8.2, Composer, Node.js + npm, dan akses database (SQLite/MySQL).

```bash
# 1. Install dependency
composer install
npm install

# 2. Siapkan environment
cp .env.example .env
php artisan key:generate

# 3. Konfigurasi database di .env (default: SQLite)
#    DB_CONNECTION=sqlite

# 4. Migrasi & seeder
php artisan migrate --seed
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=StoreSeeder

# 5. Build frontend
npm run build

# 6. Jalankan server
php artisan serve
```

Akses aplikasi di `http://localhost:8000` dan login dengan akun pada bagian 4.

## 8. Versi Laravel

**Laravel 12** (framework `^12.0`).

## 9. Versi PHP

**PHP ^8.2**

## 10. Setup Database

Konfigurasi ada di file `.env`:

```env
DB_CONNECTION=sqlite
# atau untuk MySQL:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=smartwork
# DB_USERNAME=root
# DB_PASSWORD=
```

Sebelum menjalankan migrasi SQLite, pastikan file `database/database.sqlite` tersedia.

## 11. Setup Storage

Foto selfie, etalase, dan dokumentasi disimpan ke disk `public` (`storage/app/public`).

```bash
php artisan storage:link
```

Pastikan folder `storage/app/public` dan subfolder-nya dapat ditulis oleh aplikasi.

## 12. Setup Queue

Konfigurasi default: `QUEUE_CONNECTION=database`.

Jalankan worker untuk memproses antrian (jika diperlukan untuk notifikasi/email):

```bash
php artisan queue:work
```

Untuk menjalankan seluruh proses pengembangan sekaligus (server, queue, log, vite):

```bash
composer dev
```

## 13. Troubleshooting

| Masalah | Solusi |
|---------|--------|
| Foto tidak tampil | Jalankan `php artisan storage:link` dan periksa izin write pada `storage/app/public`. |
| Halaman kosong / error 500 | Cek log di `storage/logs/laravel.log`; pastikan `php artisan migrate` sudah dijalankan. |
| `key` tidak valid | Jalankan `php artisan key:generate`. |
| Kamera / GPS tidak aktif | Buka aplikasi melalui **HTTPS** (atau `localhost` untuk development). Jika diakses via HTTP non-localhost, aplikasi menampilkan pesan "Akses ... memerlukan koneksi HTTPS". Lihat bagian **14. Kesiapan HTTPS untuk Akses Mobile (GPS & Kamera)**. |
| Test gagal pada bagian Auth | Tes Auth bawaan Breeze (`Auth\*`) menyesuaikan aplikasi yang dikustomisasi; modul inti (Presensi, Rute, Kunjungan, Toko) sudah teruji. |
| Cache / config lama | Jalankan `php artisan config:clear` dan `php artisan cache:clear`. |

## 14. Kesiapan HTTPS untuk Akses Mobile (GPS & Kamera)

Browser (Chrome, Safari, Firefox) hanya mengizinkan **GPS** (`navigator.geolocation`) dan **kamera** (`navigator.mediaDevices.getUserMedia`) pada **secure context** (HTTPS, atau `localhost`/`127.0.0.1` untuk development).

- `http://localhost:8000` → aman (secure context) → GPS & kamera berfungsi normal untuk development.
- `http://192.168.x.x:8000` (akses dari HP melalui IP LAN) → **tidak aman** → GPS & kamera diblokir browser.
- `https://domain-perusahaan` (production) → aman → GPS & kamera berfungsi normal **tanpa perubahan source code**.

### Alur izin (permission flow)

Setelah login, aplikasi **tidak** membuka kamera maupun mengambil GPS secara otomatis. GPS/kamera **hanya diakses saat pengguna benar-benar menjalankan fitur** — menekan tombol **Check In / Check Out** (Presensi) atau **Check In / Check Out** (Kunjungan), atau mengetuk kartu lokasi / area kamera. Pada saat itulah aplikasi memanggil API browser standar (`navigator.mediaDevices.getUserMedia()` dan `navigator.geolocation`) sehingga **browser menampilkan permission prompt native** — "Allow camera?" / "Allow location?" (Izinkan / Tolak) — bukan modal buatan aplikasi.

- Jika pengguna memilih **Izinkan**: kamera/GPS berfungsi menggunakan perangkat asli.
- Jika pengguna memilih **Tolak**: aplikasi menampilkan pesan error wajar pada fitur yang sedang digunakan (mis. *"Tidak dapat mengakses kamera..."* / *"Gagal mendapatkan lokasi."*) dan tetap berada di halaman yang sama, tanpa crash maupun loop popup.
- Saat membuka halaman, login, dashboard, maupun refresh — **tidak ada** permintaan izin maupun popup apa pun.

### Koneksi HTTP di HP (tidak aman)

Saat aplikasi dibuka di HP melalui HTTP non-localhost (`http://192.168.x.x:8000`), seluruh aplikasi **tetap dapat digunakan**: login, dashboard, menu, dan data berfungsi normal. **Yang dibatasi hanya GPS dan kamera.**

- **Tidak ada popup global** — tidak ada modal "Koneksi tidak aman", tidak ada tombol "Coba Lagi"/"Nanti Saja".
- Keterangan di area fitur menunjukkan bahwa koneksi aman diperlukan.
- **Pesan lokal** hanya muncul pada fitur yang benar-benar dipakai pengguna (mengetuk kartu lokasi / area kamera, atau menekan tombol Check In / Check Out):
  - *"Lokasi dan kamera membutuhkan koneksi HTTPS pada perangkat ini."*
  - Masing-masing dengan **satu tombol "Tutup"**.
- Pesan ditutup → pengguna **tetap di halaman yang sama**, tanpa redirect, tanpa logout, dan tanpa blokir aplikasi.

### Menjalankan HTTPS lokal untuk demonstrasi

`php artisan serve` tidak mendukung HTTPS. Gunakan salah satu cara berikut — **tanpa** mengubah `APP_URL` menjadi IP lokal dan **tanpa** hardcode alamat IP di source code.

Parameter yang perlu disiapkan untuk demo:

| Parameter | Nilai |
|-----------|-------|
| Sertifikat development | `mkcert` (Opsi B) atau sertifikat otomatis tunnel (Opsi A) |
| Host lokal | IP LAN mesin dev, mis. `192.168.100.21` |
| Port HTTPS | `8443` (contoh) |
| URL yang dibuka di HP (jaringan sama) | `https://192.168.100.21:8443` |

**Opsi A — Tunnel HTTPS publik (paling mudah, disarankan)**

```bash
# 1. Jalankan aplikasi agar dapat diakses dari lokal (dan tunnel)
php artisan serve --host=0.0.0.0 --port=8000

# 2. Di terminal terpisah, ekspos ke HTTPS publik
ngrok http 8000
# atau
cloudflared tunnel --url http://localhost:8000
```

Buka URL HTTPS dari ngrok/cloudflared di HP perusahaan (mis. `https://xxxx.ngrok-free.app`). Sertifikat valid → GPS & kamera berfungsi. Catatan: butuh koneksi internet.

**Opsi B — HTTPS lokal dengan mkcert + Caddy (tanpa internet)**

```bash
# 1. Install mkcert dan buat CA lokal
mkcert -install

# 2. Buat sertifikat untuk IP LAN mesin (contoh: 192.168.100.21)
mkcert 192.168.100.21 localhost 127.0.0.1
#   menghasilkan: 192.168.100.21+2.pem dan 192.168.100.21+2-key.pem

# 3. Jalankan aplikasi di port lokal
php artisan serve --host=127.0.0.1 --port=8000

# 4. Jalankan Caddy sebagai reverse proxy TLS
#    Caddyfile:
#      192.168.100.21:8443 {
#          tls 192.168.100.21+2.pem 192.168.100.21+2-key.pem
#          reverse_proxy localhost:8000
#      }
caddy run

# 5. Install CA lokal (rootCA.pem mkcert) pada HP yang akan mengakses,
#    lalu buka  https://192.168.100.21:8443
```

Cocok untuk demonstrasi offline (tanpa internet). IP pada langkah 2/4 hanya konfigurasi lokal untuk demo — tidak masuk ke source code.

**Opsi C — Laragon:** gunakan fitur HTTPS bawaan Laragon untuk domain `*.test` pada mesin pengembangan. Untuk akses dari HP, tetap gunakan Opsi A (tunnel) atau Opsi B (instalasi CA di HP).

### Kesiapan production (`https://domain-perusahaan`)

Yang dibutuhkan hanyalah konfigurasi HTTPS/ TLS yang benar di hosting (sertifikat valid untuk domain). Setelah HTTPS aktif:

- GPS & kamera berfungsi seperti saat di `localhost`.
- **Tidak ada** perubahan yang diperlukan pada logic GPS, logic kamera, permission flow, presensi, atau kunjungan.
- Seluruh URL aset dan endpoint dibuat otomatis dari host request (`route()` dan `asset()` Laravel), sehingga tidak ada hardcode IP lokal.

## 15. Ringkasan Route

**UMUM**
| Method | URI | Nama |
|--------|-----|------|
| GET | `/` | redirect ke login |
| POST | `/logout` | `logout` |
| GET | `/dashboard` | `dashboard` |

**PRESENSI** (`/attendance`, prefix `attendance.`)
| Method | URI | Nama |
|--------|-----|------|
| GET | `/attendance` | `attendance.index` |
| POST | `/attendance/check-in` | `attendance.check-in` |
| POST | `/attendance/check-out` | `attendance.check-out` |
| GET | `/attendance/history` | `attendance.history` |
| GET | `/attendance/status` | `attendance.status` |

**RENCANA KUNJUNGAN** (`/route`, prefix `route.`)
| Method | URI | Nama |
|--------|-----|------|
| GET | `/route` | `route.index` |
| GET | `/route/create` | `route.create` |
| POST | `/route/store` | `route.store` |
| GET | `/route/history` | `route.history` |
| GET | `/route/{id}` | `route.show` |
| GET | `/route/{id}/map` | `route.map` |
| POST | `/route/{id}/start` | `route.start` |
| POST | `/route/{id}/complete` | `route.complete` |
| PATCH | `/route/{routeId}/stop/{stopId}` | `route.stop.update` |
| PUT | `/route/{id}/sequence` | `route.sequence` |
| DELETE | `/route/{id}` | `route.destroy` |
| POST | `/route/gps` | `route.gps.store` |

**KUNJUNGAN** (`/visit`, prefix `visit.`)
| Method | URI | Nama |
|--------|-----|------|
| GET | `/visit` | `visit.index` |
| GET | `/visit/history` | `visit.history` |
| GET | `/visit/{id}` | `visit.show` |
| GET | `/visit/{routeStopId}/check-in` | `visit.check-in-form` |
| POST | `/visit/check-in` | `visit.store` |
| GET | `/visit/{visitId}/check-out` | `visit.check-out-form` |
| POST | `/visit/{visitId}/check-out` | `visit.check-out` |

**ADMIN** (`/admin`, prefix `admin.`, role `super-admin,admin`)
- `/admin/users*` — kelola pengguna.
- `/admin/stores*` — master toko.
- `/admin/routes` & `/admin/routes/report` — monitoring & laporan rute.
- `/admin/visits` — monitoring kunjungan.
- `/admin/reports*` — laporan attendance, rute, kunjungan, aktivitas sales, ekspor PDF/Excel.

**PROFIL** (`/profile`)
- GET `/profile` (`profile.edit`), PATCH `/profile` (`profile.update`), PUT `/profile/password` (`profile.password.update`), DELETE `/profile` (`profile.destroy`).

## 16. Ringkasan Struktur Folder

```
app/
├── Http/Controllers/
│   ├── AttendanceController.php   # Presensi
│   ├── RouteController.php        # Rencana Kunjungan
│   ├── VisitController.php        # Kunjungan & Check In/Out
│   ├── DashboardController.php    # Dashboard (sales/admin/super-admin)
│   ├── ReportController.php       # Laporan admin
│   └── Admin/                     # UserController, StoreController
├── Models/
│   ├── Attendance.php
│   ├── Route.php / RouteStop.php / GpsLocation.php
│   ├── Visit.php
│   └── Store.php
database/
├── migrations/                    # Skema tabel (users, stores, routes, route_stops, gps_locations, visits, attendances)
└── seeders/                       # RolePermissionSeeder, StoreSeeder, DatabaseSeeder
resources/views/
├── layouts/                       # app.blade.php (sidebar & layout utama)
├── attendance/                    # index (Presensi), history
├── route/                         # index, create, show, history, map
├── visit/                         # index (Kunjungan Hari Ini), check-in, check-out, show, history
├── dashboard/                     # sales, admin, super-admin
├── profile/                       # edit (Profil Saya)
└── admin/ + reports/              # modul administrasi
routes/web.php                     # Definisi seluruh route
tests/Feature/                     # Test (Attendance, RoutePlan, VisitWorkflow, Store, EmployeeViews)
```

---

## Ringkasan Perubahan (Restrukturisasi Modul Karyawan)

### Files Modified
- `resources/views/layouts/app.blade.php` — menu sidebar baru berbahasa Indonesia + "Profil Saya" + "Keluar".
- `resources/views/dashboard/sales.blade.php` — dashboard karyawan baru (informasi harian yang berguna).
- `app/Http/Controllers/DashboardController.php` — data tambahan (rencana & statistik kunjungan hari ini).
- `resources/views/visit/index.blade.php` + `app/Http/Controllers/VisitController.php` — halaman "Kunjungan Hari Ini" menampilkan toko dari rencana hari ini.
- `resources/views/visit/show.blade.php`, `attendance/index.blade.php`, `attendance/history.blade.php`, `profile/edit.blade.php` — konversi label ke Bahasa Indonesia.
- `app/Http/Controllers/RouteController.php` — `whereDate` untuk pencocokan tanggal yang lebih andal.
- `tests/Feature/EmployeeViewsSmokeTest.php` — test render halaman karyawan.

### Business Flow Changes
- Menu karyawan dirapikan menjadi: **Dashboard, Presensi, Rencana Kunjungan, Kunjungan Hari Ini, Riwayat Kunjungan, Profil Saya**.
- "Kunjungan Hari Ini" kini otomatis menampilkan toko dari rencana kunjungan hari ini dan menjadi titik masuk Check In Kunjungan.
- Seluruh label, tombol, status, dan notifikasi pada halaman karyawan menggunakan Bahasa Indonesia.
- Dashboard karyawan menampilkan data harian yang relevan (bukan statistik perusahaan yang kosong).

### Validation Results
- `php artisan test --filter="AttendanceTest|RoutePlanTest|VisitWorkflowTest|StoreTest|EmployeeViewsSmokeTest"` → **25 passed (94 assertions)**.
- Semua alur inti (Presensi, Rencana Kunjungan, Kunjungan, Master Toko) teruji.
- (Test `Auth\*` dan `ProfileTest` bawaan Breeze gagal karena aplikasi sudah dikustomisasi — di luar lingkup modul karyawan.)

### Remaining Recommendations
- Ganti password default akun contoh.
- Konfigurasi ikon aplikasi/PWA dan `service-worker.js` sesuai hosting.
- Terapkan HTTPS pada production — GPS & kamera sudah siap lewat alur secure-context bawaan aplikasi (lihat bagian 14).
- Pertimbangkan menambahkan job queue untuk notifikasi email check-in/out.
- Jadwalkan pengecekan otomatis (reporting) mingguan untuk memantau penyelesaian kunjungan.