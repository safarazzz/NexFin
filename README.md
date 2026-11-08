# NexFin - Platform Pengelolaan Keuangan Digital

Selamat datang di repositori **NexFin**! Proyek ini dirancang untuk mendemonstrasikan perbandingan dua pendekatan arsitektur perangkat lunak dalam satu studi kasus sistem keuangan:
1. **Layered Architecture** 
2. **Non-Layered Architecture**

Seluruh berkas diagram Mermaid murni disimpan secara modular di dalam folder [`design`].

---

## 📑 Daftar Isi

1. [Peran Pengguna & Matriks Hak Akses](#-peran-pengguna--matriks-hak-akses)
2. [Penerapan Konsep Pemrograman Berorientasi Objek (OOP)](#-penerapan-konsep-pemrograman-berorientasi-objek-oop)
3. [Perbandingan Arsitektur: Layered vs Non-Layered](#-perbandingan-arsitektur-layered-vs-non-layered)
4. [Daftar Kebutuhan Halaman & Navigasi](#-daftar-kebutuhan-halaman--navigasi)
5. [Basis Data & Kredensial Pengujian (Dummy Data)](#-basis-data--kredensial-pengujian-dummy-data)
6. [Struktur Direktori Repositori](#-struktur-direktori-repositori)

---

## 👥 Peran Pengguna & Matriks Hak Akses

Sistem NexFin memiliki 3 tingkatan pengguna dengan pembagian peran yang ketat:

| Fitur / Aksi | Pelanggan | Admin | Super Admin | Aturan Bisnis & Validasi |
| :--- | :---: | :---: | :---: | :--- |
| **Registrasi Mandiri** | ✅ | ❌ | ❌ | Format email & no HP valid, password di-hash (Bcrypt). |
| **Edit Profil Sendiri** | ✅ | ✅ | ✅ | Mengubah nama, email, no HP, dan ganti password. |
| **Top Up & Tarik Saldo** | ✅ | ❌ | ❌ | Nominal > 0. Tarik saldo $\le$ saldo saat ini. Akun wajib aktif. |
| **Transfer Saldo** | ✅ | ❌ | ❌ | Nominal $\ge$ Rp 10.000. Akun pengirim & penerima wajib aktif. |
| **Lihat Semua Pengguna** | ❌ | ✅ | ✅ | Admin hanya *read-only* (tidak bisa edit/hapus profil user lain). |
| **Searching Pelanggan** | ❌ | ✅ | ✅ | Filter berdasarkan nama, email, no HP, atau status aktif. |
| **Aktifkan / Nonaktifkan Akun** | ❌ | ✅ (Pelanggan) | ✅ (Pelanggan & Admin) | Mencatat log audit (siapa, target, waktu, dan alasan). |
| **CRUD Pelanggan & Admin** | ❌ | ❌ | ✅ | Super Admin dapat menambah, mengubah, dan menghapus akun. |

---

## 🧩 Penerapan Konsep Pemrograman Berorientasi Objek (OOP)

👉 **Diagram Desain:** [`design/oop-class-diagram.md`]

Sistem dirancang mengadopsi prinsip OOP utama:

1. **Inheritance (Pewarisan):**
   * Kelas abstrak `User` mendefinisikan atribut umum (`id`, `nama_depan`, `nama_belakang`, `no_hp`, `email`, `password`, `status_aktif`) dan method umum (`editProfil()`, `changePassword()`, `verifyPassword()`).
   * `Pelanggan` mewarisi `User` dengan tambahan atribut khusus `saldo` serta method mutasi keuangan (`topUpSaldo()`, `tarikSaldo()`, `transferSaldo()`).
   * `Admin` mewarisi `User` dengan kapabilitas supervisi (`searchPelanggan()`, `toggleStatusUser()`).
   * `SuperAdmin` mewarisi `Admin` dengan kapabilitas master CRUD (`createPelanggan()`, `deletePelanggan()`, `createAdmin()`, `deleteAdmin()`).
2. **Encapsulation (Pembungkusan Data):**
   * Password disimpan dalam bentuk hash terenkripsi (Bcrypt) dan tidak boleh diakses langsung tanpa melalui method verifikasi.
   * Saldo pelanggan hanya bisa dimutasi melalui method validasi transaksi (mencegah manipulasi saldo minus).

---

## 🏛️ Perbandingan Arsitektur: Layered vs Non-Layered

👉 **Diagram Desain:**
* [Perbandingan Arsitektur]
* [Alur Eksekusi Layered (Sequence Diagram)]
* [Alur Eksekusi Non-Layered (Sequence Diagram)]

### 1. Arsitektur Non-Layered (Transaction Script / Fat Controller)
* **Karakteristik:** Satu fungsi/file controller mengeksekusi semua hal dari hulu ke hilir (menerima HTTP, validasi regex manual, hashing password, query SQL langsung, hingga return response).
* **Kelebihan:** Cepat dan mudah dipahami saat prototyping awal aplikasi kecil.
* **Kekurangan:** Sulit di-*unit test*, logika bisnis bercampur aduk dengan SQL, rawan duplikasi kode di berbagai endpoint.

### 2. Arsitektur Layered (3-Tier: Presentation $\rightarrow$ Business $\rightarrow$ Data Access)
* **Presentation Layer (Controller):** Murni menangani komunikasi HTTP (membaca request body/query dan mengirim HTTP status).
* **Business Logic Layer (Service):** Otak sistem yang memvalidasi format data, hashing password, mengecek kecukupan saldo, dan memverifikasi status akun aktif.
* **Data Access Layer (Repository):** Lapisan tunggal yang berkomunikasi langsung dengan database SQL.
* **Kelebihan:** Pemisahan tanggung jawab tinggi (*Separation of Concerns*), mudah diuji secara modular (*Unit Testing* dengan mock), mudah dirawat saat aplikasi membesar.

### Tabel Perbandingan Teknis

| Parameter Evaluasi | Non-Layered Architecture | Layered Architecture |
| :--- | :--- | :--- |
| **Separation of Concerns** | Rendah (Semua kode tercampur di controller) | Tinggi (Controller, Service, Repo terpisah) |
| **Kemudahan Pengujian (Testability)** | Sulit (Wajib menyalakan database & HTTP) | Sangat mudah (Service bisa diuji dengan Mock Repo) |
| **Reusability Kode** | Rendah (Pengecekan saldo rawan copy-paste) | Tinggi (Method repository & service dapat dipakai ulang) |
| **Boilerplate Awal** | Minimal | Memerlukan struktur class/interface di awal |

---

## 🖥️ Daftar Kebutuhan Halaman & Navigasi

👉 **Diagram Desain:** [`design/sitemap-navigation.md`]

Berikut kebutuhan antarmuka sistem:
1. **Login Page:** Akses autentikasi untuk semua role. Jika akun berstatus nonaktif, sistem akan menampilkan notifikasi penolakan akses.
2. **Register Page:** Khusus pendaftaran mandiri calon pelanggan baru.
3. **Home & Dashboard Page:** Ringkasan informasi, total saldo pelanggan, atau statistik user untuk Admin.
4. **Search Page (Admin & Super Admin):** Fitur pencarian data pelanggan (berdasarkan nama, email, no HP, atau status aktif) serta tombol toggle status akun.
5. **Wallet Hub (Top Up, Transfer, Tarik Saldo):** Halaman pengelolaan dana pelanggan untuk pengisian saldo, transfer antar-pengguna, dan penarikan dana.
6. **Riwayat Transaksi:** Menampilkan mutasi transaksi (debit/kredit) beserta sisa saldo akhir.
7. **Profile Page:** Halaman untuk semua user mengedit nama, kontak, dan memperbarui kata sandi.
8. **User Management Page (Super Admin):** Pengelolaan penuh (CRUD) akun pelanggan dan admin.

---

## 🗄️ Basis Data & Kredensial Pengujian (Dummy Data)

👉 **Diagram ERD:** [`design/erd.md`]
👉 **Berkas Database:** [`db/database.sql`]

Seluruh skema database dan data dummy disatukan ke dalam **satu berkas tunggal** agar praktis di-import.

### Struktur Tabel Utama:
1. **`users`**: Menyimpan data identitas, role (`pelanggan`, `admin`, `super_admin`), `status_aktif`, dan `saldo`.
2. **`transactions`**: Mencatat histori transaksi (`TOPUP`, `TRANSFER`, `WITHDRAW`), nominal, dan saldo akhir.
3. **`user_status_logs`**: Mencatat audit trail saat akun dinonaktifkan/diaktifkan kembali beserta alasan.

### Cara Import Database:
* **Melalui phpMyAdmin (XAMPP):** Buka `http://localhost/phpmyadmin` $\rightarrow$ klik tab **Import** $\rightarrow$ pilih berkas [`db/database.sql`] klik **Kirim / Import**.
* **Melalui Terminal:**
  ```bash
  mysql -u root -p < db/database.sql
  ```

### Kredensial Akun Dummy untuk Testing:
Semua akun pengujian menggunakan password yang sama:  
👉 **`password123`** *(Tersimpan dalam bentuk Bcrypt Hash: `$2y$10$HQB...`)*

| ID | Nama Pengguna | Email | No HP | Role | Status | Saldo | Skenario Pengujian |
| :---: | :--- | :--- | :--- | :--- | :---: | :---: | :--- |
| **1** | Super Admin | `superadmin@nexfin.com` | `081200000001` | `super_admin` | Aktif | Rp 0 | Uji coba CRUD Admin & Pelanggan |
| **2** | Budi Santoso | `admin.budi@nexfin.com` | `081200000002` | `admin` | Aktif | Rp 0 | Uji coba Searching & Freeze Akun |
| **3** | Siti Aminah | `admin.siti@nexfin.com` | `081200000003` | `admin` | Aktif | Rp 0 | Uji coba operasional Admin |
| **4** | Ahmad Fauzi | `ahmad.fauzi@gmail.com` | `081234567890` | `pelanggan` | Aktif | Rp 1.500.000 | Pengirim transfer & tarik dana |
| **5** | Dewi Lestari | `dewi.lestari@gmail.com` | `081234567891` | `pelanggan` | Aktif | Rp 750.000 | Penerima transfer dana |
| **6** | Rian Pratama | `rian.pratama@gmail.com` | `081234567892` | `pelanggan` | Aktif | Rp 250.000 | Pelanggan saldo menengah |
| **7** | Doni Kusuma | `doni.fraud@gmail.com` | `081234567899` | `pelanggan` | **Nonaktif** | Rp 50.000 | Uji coba penolakan login akun terblokir |

---

## 🗂️ Struktur Direktori Repositori

```text
NexFin/
├── README.md                      # Dokumentasi komprehensif proyek NexFin
├── db/
│   └── database.sql               # Berkas tunggal DDL & data dummy awal
├── design/                        # Berkas diagram Mermaid murni (Desain Saja)
│   ├── architecture-comparison.md # Diagram perbandingan konsep arsitektur
│   ├── erd.md                     # Entity Relationship Diagram tabel database
│   ├── layered-flow.md            # Sequence Diagram alur Layered Architecture
│   ├── non-layered-flow.md        # Sequence Diagram alur Non-Layered Architecture
│   ├── oop-class-diagram.md       # Class Diagram menerapkan konsep OOP
│   └── sitemap-navigation.md      # Diagram alur antarmuka & navigasi halaman
├── Layered/                       # Implementasi kode Layered Architecture
└── non-Layered/                   # Implementasi kode Non-Layered Architecture
```
