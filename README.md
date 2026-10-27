# LMS SMP Sekar Pertiwi

Learning Management System untuk SMP Sekar Pertiwi dengan chatbot akademik
(n8n + Google Gemini). Dibangun dengan **Laravel 13**, **MySQL**, **Tailwind CSS 4**
dan **Alpine.js**.

| Peran | Yang bisa dilakukan |
|-------|---------------------|
| **Admin** | Mengelola akun guru dan siswa, kelas, mata pelajaran, penugasan guru (guru ↔ mapel ↔ kelas) dan penempatan siswa ke kelas. Admin tidak ikut forum dan chatbot. |
| **Guru** | Membuat materi dan mengunggah PDF (otomatis diindeks untuk chatbot), membuat tugas, menilai pengumpulan siswa, memoderasi forum kelasnya, memakai chatbot. |
| **Siswa** | Melihat materi terbit di kelasnya, mengunduh PDF, mengumpulkan tugas (otomatis berstatus *tepat waktu* atau *terlambat*), melihat nilai, ikut forum, bertanya ke chatbot **Sekar AI**. |

Chatbot memakai pencarian teks MySQL (`FULLTEXT`, tanpa vektor). n8n hanya
mengambil potongan materi dari Laravel, meminta Gemini menyusun jawaban, lalu
mengembalikannya beserta sumber (judul materi, berkas, halaman).

---

## 1. Kebutuhan

* **Laragon** (Windows) dengan PHP **8.3 atau lebih baru**, **MySQL 8** (atau MariaDB 10.5+; `FULLTEXT` InnoDB wajib), Apache atau Nginx.
* **Composer** (sudah ada di Laragon).
* **Node.js tidak wajib.** CSS/JS sudah dibangun di `public/build`. Node hanya perlu bila kamu mengubah tampilan (`npm install && npm run build`).
* Untuk chatbot sungguhan: **n8n** dan **API key Google AI Studio** (lihat [`n8n/README.md`](n8n/README.md)). Tanpa itu seluruh LMS tetap berjalan; chatbot menampilkan pesan "tidak dapat dihubungi".

## 2. Pemasangan di Laragon

1. **Salin proyek** ke `C:\laragon\www\lms-sekolah`.
   Laragon otomatis membuat alamat `http://lms-sekolah.test` (menu Laragon → *Apache/Nginx* → *Reload*; pastikan *Auto virtual hosts* aktif).

2. **Buat database** (Laragon → *Database* → HeidiSQL, atau terminal Laragon):

   ```sql
   CREATE DATABASE lms_sekolah CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE DATABASE lms_sekolah_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; -- hanya untuk menjalankan tes
   ```

3. **Pasang dependensi** (terminal Laragon, di folder proyek):

   ```
   composer install
   copy .env.example .env
   php artisan key:generate
   ```

4. **Isi `.env`**:

   | Kunci | Isi |
   |-------|-----|
   | `APP_URL` | `http://lms-sekolah.test` |
   | `DB_*` | Laragon bawaan: user `root`, password kosong, database `lms_sekolah` |
   | `ADMIN_NOMOR_INDUK`, `ADMIN_PASSWORD` | akun admin pertama (**ganti passwordnya**) |
   | `N8N_API_TOKEN`, `N8N_WEBHOOK_SECRET` | dua string acak berbeda, buat dengan `php -r "echo bin2hex(random_bytes(32));"` |
   | `N8N_WEBHOOK_INDEKS`, `N8N_WEBHOOK_TANYA` | alamat webhook *produksi* n8n (bawaan sudah benar bila n8n di `localhost:5678`) |

5. **Buat tabel dan data awal** (akun admin + FAQ):

   ```
   php artisan migrate --seed
   ```

6. **Naikkan batas unggah PHP** (Laragon → *Menu* → *PHP* → *php.ini*):
   `upload_max_filesize = 25M` dan `post_max_size = 30M`, lalu restart Apache/Nginx.
   Batas aplikasi: PDF materi 20 MB, berkas tugas 10 MB (ubah lewat `BATAS_UNGGAH_*` di `.env`).

7. Buka **http://lms-sekolah.test** dan masuk sebagai admin dengan nomor induk dan password dari `.env`.

Berkas yang diunggah tersimpan di `storage/app/private/...` (tidak bisa diakses langsung lewat URL; semua unduhan melewati pemeriksaan hak akses). `storage:link` tidak diperlukan.

### Data contoh (opsional)

Untuk melihat tampilan terisi (5 guru, 10 siswa, kelas, materi PDF berteks, tugas, nilai, forum):

```
php artisan db:seed --class=DemoSeeder
```

| Peran | Nomor induk | Password |
|-------|-------------|----------|
| Admin | nilai `ADMIN_NOMOR_INDUK` | nilai `ADMIN_PASSWORD` |
| Guru | `guru01` … `guru05` | `password123` |
| Siswa | `2026001` … `2026010` | `password123` |

> Hanya untuk pengembangan dan demo. Perintah ini menolak berjalan bila `APP_ENV=production`.

## 3. Alur singkat

1. **Admin**: buat kelas dan mata pelajaran → buat akun guru dan siswa → *Penugasan guru* (guru mengampu mapel X di kelas Y) → *Penempatan siswa* ke kelas.
2. **Guru**: *Materi → Tambah* → pilih kelas/mapel, unggah PDF (berisi teks, bukan hasil scan), pilih **Terbit**. Status berkas berubah **Menunggu → Diproses → Terindeks**. Buat *Tugas* dengan tenggat; setelah siswa mengumpulkan, beri nilai dan komentar.
3. **Siswa**: lihat materi dan tugas kelasnya, kumpulkan tugas, tanya Sekar AI. Jawaban selalu menyertakan sumber; bila tidak ada di materi, Sekar AI berkata tidak menemukan (tidak mengarang).

Aturan akses utama: siswa hanya melihat materi **terbit** di kelasnya sendiri; guru hanya mengelola kelas/mapel yang ia ampu; chatbot hanya mencari di materi terbit milik kelas penanya (Laravel yang menentukan, bukan n8n).

## 4. Chatbot (n8n + Gemini)

Dua workflow siap impor ada di folder [`n8n/`](n8n):

* `01-indeks-dokumen.json` – dipicu Laravel saat PDF diunggah/diganti/diindeks ulang.
* `02-tanya-jawab.json` – dipicu Laravel saat siswa/guru bertanya.

Panduan lengkap (kredensial, URL, uji, pemecahan masalah) ada di [`n8n/README.md`](n8n/README.md).

**Mencoba tanpa n8n dan tanpa Gemini:** folder `n8n/simulator` berisi simulator yang menjalankan kode node yang sama dengan workflow dan menjawab dengan kutipan dari materi. Berguna untuk memastikan sisi Laravel benar sebelum n8n dikonfigurasi, dan untuk demo offline.

## 5. Pengujian

```
php artisan test
```

131 tes (login, hak akses tiap peran, materi dan unggah PDF, tugas dan penilaian, forum, API untuk n8n, chatbot dengan n8n yang ditiru). Tes memakai database `lms_sekolah_test` (user/password dari `phpunit.xml`: `root` tanpa password; ubah di sana bila berbeda) dan **mengosongkan tabelnya setiap tes**, jadi jangan arahkan ke database berisi data asli.

## 6. Struktur penting

```
app/Http/Controllers/        Admin/, Guru/, Siswa/, Api/N8n/, Forum, Chatbot, Dashboard
app/Http/Middleware/         EnsurePeran (alias "peran:"), PastikanAkunAktif, AuthenticateN8nToken, BuangQueryArray
app/Policies/                Materi, Tugas, PengumpulanTugas, ForumTopik, ForumBalasan
app/Services/                BerkasMateriService, ChatbotService, N8nClient
app/Observers/               BerkasMateriObserver  (unggah → job KirimBerkasKeN8n)
app/Jobs/                    KirimBerkasKeN8n
database/migrations/         13 tabel domain + sesi/antrean/cache
resources/views/             Blade (layout, komponen, admin, guru, siswa, forum, chatbot)
resources/css, resources/js  Tailwind 4 + Alpine (dibangun ke public/build)
routes/web.php, api.php      web per peran; /api/n8n/* untuk n8n (token Bearer)
n8n/                         workflow n8n + simulator
tests/                       PHPUnit
```

Kata sandi disimpan ter-hash (bcrypt). Rute n8n dilindungi token Bearer; webhook n8n dilindungi header `X-N8N-Secret`. Unggahan diperiksa isinya (bukan hanya ekstensi) dan disimpan dengan nama acak.

## 7. Pemecahan masalah

| Gejala | Penyebab / solusi |
|--------|-------------------|
| `SQLSTATE ... Unknown database` | Database belum dibuat (langkah 2) atau `DB_DATABASE` salah. |
| Halaman putih tanpa gaya | `public/build` hilang. Jalankan `npm install && npm run build`. |
| Unggah PDF ditolak "melebihi batas" | `upload_max_filesize`/`post_max_size` php.ini terlalu kecil (langkah 6). |
| Status berkas langsung **Gagal – n8n tidak dapat dihubungi** | n8n belum jalan, atau `N8N_WEBHOOK_INDEKS` salah/workflow belum di-*publish*. Setelah diperbaiki tekan ikon **indeks ulang** pada berkas. |
| Berkas **Gagal – PDF tidak berisi teks** | PDF hasil scan/foto. Pakai PDF yang teksnya bisa diseleksi. |
| Chatbot: "Maaf, chatbot sedang tidak dapat dihubungi" | n8n mati, rahasia webhook berbeda dengan `N8N_WEBHOOK_SECRET`, atau workflow Tanya Jawab belum aktif. Lihat *Executions* di n8n. |
| Chatbot selalu "tidak menemukan jawaban" | Materi belum **Terbit**, berkas belum **Terindeks**, atau siswa belum ditempatkan di kelas. Pencarian mengabaikan kata umum dan kata kurang dari 3 huruf. |
| Perubahan `.env` tidak berpengaruh | `php artisan config:clear`. |
| Antrean (`QUEUE_CONNECTION=database`) tidak jalan | Jalankan `php artisan queue:work` di terminal terpisah. Bawaan `sync` tidak butuh itu. |

## 8. Catatan pengembangan

Proyek ini dibangun dan diuji pada PHP 8.3, Laravel 13.38 dan **MariaDB 10.11 sebagai pengganti MySQL 8**. Yang **belum** diuji di lingkungan pengembangan: MySQL 8 sungguhan, Windows/Laragon, serta n8n dan Gemini yang sesungguhnya (workflow dan kode node diuji lewat simulator, nama model Gemini mengikuti dokumen rancangan dan bisa diubah di node *Konfigurasi*). Bila ada perbedaan perilaku saat pertama kali dijalankan di Laragon, mulai dari bagian *Pemecahan masalah*.
