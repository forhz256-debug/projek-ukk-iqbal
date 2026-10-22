# Aplikasi Peminjaman Alat — Versi Disesuaikan dengan Soal SPK Paket 1 (RPL)

## Apa yang diubah dari kode lama (di `peminjaman_alat.zip`)

Kode lama punya folder terpisah untuk tiap modul (alat, kategori, user, peminjaman,
detail peminjaman, pengembalian, transaksi) dengan **file `connection.php` sendiri-sendiri**,
CSS terpisah per folder, dan **tidak ada pengecekan role** di tiap halaman (siapa saja yang
sudah login — atau bahkan belum login — bisa saja mengakses file CRUD-nya kalau tahu URL-nya).
Itu sumber utama "error" dan celah keamanan yang diminta untuk diperbaiki.

Perbaikan yang dilakukan:

1. **Satu koneksi database (PDO + prepared statement)** di `config/koneksi.php`,
   dipakai semua file — bukan mysqli duplikat di 7 folder berbeda.
2. **RBAC (role-based access control)** lewat `config/auth.php`
   (`require_login()` / `require_role([...])`) di **setiap** file, sesuai tabel fitur di
   soal (lihat bagian "Matriks Fitur" di bawah). Kalau akun Peminjam mencoba buka
   `/user/index.php`, sistem menolak dengan pesan "Akses ditolak", bukan diam-diam error.
3. **Semua query pakai prepared statement** (`:param`) — kode lama rawan SQL Injection
   kalau input `$_GET`/`$_POST` langsung disambung ke query.
4. **Efisiensi query**:
   - List alat/peminjaman pakai `LIMIT`/`OFFSET` (paginasi) — sesuai poin soal
     "gunakan limit ketika menggunakan data besar".
   - Daftar alat per peminjaman diambil pakai satu query `GROUP_CONCAT` + `JOIN`,
     bukan query di dalam loop (N+1 query) seperti pola umum di kode lama.
   - Dashboard pakai `COUNT()`/`SUM()` di database, bukan tarik semua baris lalu
     dihitung di PHP.
5. **Transaksi database** (`beginTransaction`/`commit`/`rollBack`) dipakai saat:
   - Peminjam mengajukan peminjaman dengan banyak alat sekaligus,
   - Petugas menyetujui peminjaman (cek stok dulu baru dikurangi, `FOR UPDATE` supaya
     tidak race condition kalau dua petugas approve bersamaan),
   - Petugas memverifikasi pengembalian (stok dikembalikan + status di-update bersamaan).
6. **Password** memakai `password_hash()`/`password_verify()`. Password lama di data contoh
   ('12345', '123') masih **plaintext** persis seperti dump aslinya — `login.php` otomatis
   menerima itu di login pertama lalu meng-upgrade ke hash aman, jadi tidak perlu reset data.
7. **`tb_transaksi` dihapus** dari skema baru karena tidak ada di daftar fitur soal, dan
   isinya redundan dengan `tb_peminjaman` (dua tabel simpan info yang sama).
8. **Fitur yang tadinya belum ada, sekarang ditambahkan** karena diminta soal tapi tidak
   ada modulnya di kode lama: **Log Aktifitas** (`log_aktivitas/`), **Menyetujui/Menolak
   Peminjaman** (`peminjaman/setujui.php`), **Cetak Laporan** (`laporan/`, ada tombol
   Print/PDF), dan **perhitungan denda keterlambatan** otomatis saat pengembalian.

## Matriks Fitur & Akses (persis Tabel Fitur di dokumen SPK)

| Fitur                     | Admin | Petugas | Peminjam |
|---------------------------|:-----:|:-------:|:--------:|
| Login / Logout            |  ✔    |   ✔     |    ✔     |
| CRUD User                 |  ✔    |         |          |
| CRUD Alat                 |  ✔    | lihat saja | lihat saja |
| CRUD Kategori             |  ✔    |         |          |
| CRUD Data Peminjaman      |       |   ✔     |          |
| CRUD Pengembalian         |       |   ✔     |          |
| Log Aktifitas             |  ✔    |         |          |
| Menyetujui Peminjaman     |       |   ✔     |          |
| Memantau Pengembalian     |       |   ✔     |          |
| Mencetak Laporan          |  ✔    |         |          |
| Melihat Daftar Alat       |  ✔    |   ✔     |    ✔     |
| Mengajukan Peminjaman     |       |         |    ✔     |
| Mengembalikan Alat        |       |         |    ✔     |

`config/partial_header.php` adalah satu-satunya tempat menu navigasi (navbar atas) diatur berdasarkan
role, dan setiap file di baliknya dijaga dengan `require_role([...])` — jadi walau
seseorang mengetik URL langsung, tetap ditolak kalau rolenya tidak sesuai.

## Cara Install

1. Buat database lewat phpMyAdmin, lalu import `db/db_peminjaman_alat_fixed.sql`
   (skema baru — jangan pakai file `.sql` lama lagi karena struktur tabelnya berbeda).
2. Sesuaikan kredensial di `config/koneksi.php` (`$DB_HOST`, `$DB_NAME`, `$DB_USER`, `$DB_PASS`).
3. Taruh folder ini di `htdocs` (XAMPP) lalu buka `http://localhost/<folder>/login.php`.
4. Login pakai salah satu akun contoh (password masih `12345`, kecuali user `Abdul` = `123`):

   | Username   | Role     |
   |------------|----------|
   | admin      | Admin    |
   | petugas1   | Petugas  |
   | andi_h     | Peminjam |

## Alur Peminjaman → Pengembalian (ringkas)

`Diajukan` (peminjam mengajukan) → Petugas **Setujui** (stok berkurang, status jadi
`Dipinjam`) **atau Tolak** (status `Ditolak`) → Peminjam klik **Kembalikan** (status
pengembalian `Menunggu`) → Petugas **Verifikasi** (hitung denda otomatis kalau telat,
stok bertambah lagi, peminjaman jadi `Selesai`).

## Yang belum dibuat (di luar scope kode, perlu dokumen terpisah)

Sesuai poin "Langkah Kerja" di soal, aplikasi ini adalah bagian **Implementasi Kode**-nya
saja. ERD, flowchart/pseudocode (login, pinjam, kembali+denda), dokumentasi modul,
skenario pengujian, dan laporan evaluasi singkat masih perlu dibuat sebagai dokumen
terpisah — beri tahu saya kalau mau saya bantu buatkan draftnya juga (Word/PDF).

## Update: Register, Denda Telat + Rusak, Konfirmasi Pembayaran

- `register.php` — pendaftaran akun mandiri. Role otomatis **Peminjam** (Admin/Petugas hanya dibuat Admin lewat Data User). Ada link di halaman login.
- Denda dihitung saat **Petugas memverifikasi pengembalian** (`pengembalian/proses.php`):
  - Telat: Rp2.000 / hari / alat (`TARIF_TELAT_PER_HARI` di `config/fungsi.php`)
  - Kerusakan per alat: Rusak Ringan Rp10.000, Rusak Berat Rp50.000, Hilang Rp100.000 (`tarif_kerusakan()` di `config/fungsi.php`)
  - Petugas bisa mengubah kondisi & nominal sebelum menyimpan. Total > 0 → status denda **Belum Dibayar**.
- `pengembalian/bayar.php` — Petugas mengonfirmasi denda **Lunas** (bisa dibatalkan bila salah klik). Tercatat di Log Aktifitas.
- Peminjam melihat rincian denda + status di menu Pengembalian, dan **tidak bisa mengajukan peminjaman baru** selama masih ada denda belum dibayar.
- Kolom baru di `tb_pengembalian`: `denda_telat`, `denda_kerusakan`, `status_denda`, `tanggal_bayar`, `id_petugas_bayar`, `catatan_denda`.
  Database baru → import `db/db_peminjaman_alat_fixed.sql`. Database lama → jalankan `db/migrasi_denda.sql`.
