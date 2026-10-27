# Integrasi n8n + Gemini

Folder ini berisi dua workflow siap impor dan simulator n8n.

| Berkas | Fungsi |
|--------|--------|
| `01-indeks-dokumen.json` | **Workflow 1: Indeks Dokumen.** PDF materi → potongan teks per halaman → disimpan di tabel `bagian_materi`. |
| `02-tanya-jawab.json` | **Workflow 2: Tanya Jawab.** Pertanyaan → potongan materi + FAQ dari Laravel → Gemini → jawaban dengan sumber. |
| `simulator/` | n8n tiruan untuk menguji tanpa n8n dan tanpa Gemini. |

## Cara kerja

```
UNGGAH      Guru unggah PDF ─► Laravel (job KirimBerkasKeN8n)
                                   │ POST /webhook/indeks-dokumen   {berkas_id}   + X-N8N-Secret
                                   ▼
                               n8n: Ambil PDF ◄── GET  /api/n8n/berkas-materi/{id}     (Bearer token)
                                    Ekstrak Teks → Potong Teks (±1.200 karakter, tumpang tindih 150)
                                    Simpan ke Laravel ─► POST /api/n8n/berkas-materi/{id}/bagian
                               (PDF tanpa teks / gagal diunduh → status "gagal" + pesan)

TANYA       Siswa tanya ─► Laravel (ChatbotService)
                                   │ POST /webhook/tanya-jawab   {pertanyaan, user_id}   + X-N8N-Secret
                                   ▼
                               n8n: Cari Materi ◄── GET /api/n8n/pencarian-materi?user_id&q&limit
                                    Ambil FAQ    ◄── GET /api/n8n/faq
                                    Susun Prompt → Panggil Gemini → Olah Jawaban
                                    Kirim Jawaban ─► {jawaban, ditemukan, sumber[], token_masuk,
                                                      token_keluar, model, status}
                               (Gemini gagal → "Jawaban Cadangan", status "gagal")
```

Hak akses ditentukan Laravel: `pencarian-materi` hanya mengembalikan materi **terbit** milik kelas siswa (atau yang diampu guru) dari `user_id` tersebut. n8n tidak pernah memilih materi sendiri.

## A. Menyiapkan n8n

Pilih salah satu.

**Docker** (disarankan di Windows; `--add-host` agar kontainer bisa memanggil `lms-sekolah.test` milik Laragon):

```
docker run -it --rm --name n8n -p 5678:5678 ^
  --add-host=lms-sekolah.test:host-gateway ^
  -v n8n_data:/home/node/.n8n ^
  -e GENERIC_TIMEZONE=Asia/Jakarta -e TZ=Asia/Jakarta ^
  docker.n8n.io/n8nio/n8n
```

**npm**: `npx n8n` (Node.js 20+). Pada cara ini `lms-sekolah.test` dikenali langsung oleh Windows.

Buka http://localhost:5678 dan buat akun pemilik.

## B. Membuat tiga kredensial

*Credentials → Create credential → **Header Auth***, lalu buat tepat tiga ini (namanya dipakai workflow):

| Nama kredensial | Name | Value | Dipakai oleh |
|-----------------|------|-------|--------------|
| `Rahasia Webhook` | `X-N8N-Secret` | isi `N8N_WEBHOOK_SECRET` di `.env` Laravel | node Webhook kedua workflow |
| `Token Laravel` | `Authorization` | `Bearer ` + isi `N8N_API_TOKEN` (ada spasi setelah Bearer) | semua HTTP Request ke Laravel |
| `Kunci Gemini` | `x-goog-api-key` | API key dari [Google AI Studio](https://aistudio.google.com/apikey) | node Panggil Gemini |

## C. Mengimpor workflow

1. *Workflows → Create workflow → ⋯ → Import from file…* → pilih `01-indeks-dokumen.json`. Ulangi untuk `02-tanya-jawab.json`.
2. Pada tiap node yang ditandai merah (kredensial belum terpilih), pilih kredensial dari tabel di atas:
   * Workflow 1: **Terima Berkas** → *Rahasia Webhook*; **Ambil PDF** dan **Simpan ke Laravel** → *Token Laravel*.
   * Workflow 2: **Terima Pertanyaan** → *Rahasia Webhook*; **Cari Materi** dan **Ambil FAQ** → *Token Laravel*; **Panggil Gemini** → *Kunci Gemini*.
3. Buka node **Konfigurasi** pada tiap workflow dan sesuaikan:
   * `laravel_url`: alamat Laravel **dilihat dari n8n**. Bawaan `http://lms-sekolah.test`.
   * Workflow 2 saja: `model`: nama model Gemini yang akan dipakai (bawaan `gemini-3.5-flash-lite`, sesuai dokumen rancangan). Jika Gemini menjawab 404 "model not found", ganti dengan model yang tersedia di akun AI Studio-mu.
4. Simpan, lalu **Publish** (toggle *Active*) kedua workflow. URL produksi `http://localhost:5678/webhook/...` hanya hidup setelah workflow aktif.

## D. Menguji

1. Di `.env` Laravel pastikan:
   ```
   N8N_API_TOKEN=...        # sama dengan kredensial Token Laravel
   N8N_WEBHOOK_SECRET=...   # sama dengan kredensial Rahasia Webhook
   N8N_WEBHOOK_INDEKS=http://localhost:5678/webhook/indeks-dokumen
   N8N_WEBHOOK_TANYA=http://localhost:5678/webhook/tanya-jawab
   ```
   lalu `php artisan config:clear`.
2. Login sebagai guru → *Materi → Tambah* → unggah PDF berteks, status **Terbit**. Dalam beberapa detik lencana berkas menjadi **Terindeks** (muat ulang halaman). Di n8n, menu *Executions* menampilkan eksekusi hijau.
3. Login sebagai siswa di kelas itu → **Sekar AI** → tanyakan isi PDF. Jawaban muncul dengan blok **Sumber** (materi, berkas, halaman).
4. Tanyakan hal di luar materi: Sekar AI harus menjawab tidak menemukan dan tidak mencantumkan sumber.
5. Uji jalur gagal: unggah PDF hasil scan → status **Gagal** dengan pesan *PDF tidak berisi teks*.

Uji cepat tanpa Laravel (PowerShell; ganti `RAHASIA`, dan `7` dengan id siswa):

```
Invoke-RestMethod -Method Post -Uri http://localhost:5678/webhook/tanya-jawab `
  -Headers @{ 'X-N8N-Secret' = 'RAHASIA' } -ContentType 'application/json' `
  -Body '{"pertanyaan":"Apa itu fotosintesis?","user_id":7}'
```

## E. Kontrak antarmuka (bila ingin membangun ulang workflow sendiri)

Semua permintaan n8n → Laravel membawa `Authorization: Bearer <N8N_API_TOKEN>` dan `Accept: application/json`.

| Metode & alamat | Keterangan |
|-----------------|------------|
| `GET /api/n8n/berkas-materi/{id}` | Mengunduh PDF. |
| `POST /api/n8n/berkas-materi/{id}/bagian` | Badan: `{status: "terindeks"\|"gagal", pesan, jumlah_halaman, bagian: [{nomor_bagian, halaman, teks}]}`. Menggantikan seluruh bagian lama berkas itu (aman dipanggil berulang). |
| `DELETE /api/n8n/berkas-materi/{id}/bagian` | Mengosongkan indeks (opsional). |
| `GET /api/n8n/pencarian-materi?user_id=&q=&limit=` | Maksimal 8 potongan, urut skor `FULLTEXT`. |
| `GET /api/n8n/faq` | FAQ aktif. |

Balasan webhook **Tanya Jawab** harus berupa JSON dengan `jawaban`, `status` (`berhasil` atau `gagal`), `sumber` (daftar `{no, materi_id, materi, mapel, berkas_id, berkas, halaman}`), `token_masuk`, `token_keluar`. Laravel mencatat semuanya ke `riwayat_chat`; bila `status` bukan `berhasil`, sumber dikosongkan.

## F. Simulator (tanpa n8n dan tanpa Gemini)

Menjalankan kode node *Potong Teks*, *Laporan Gagal*, *Susun Prompt*, *Olah Jawaban* dan *Jawaban Cadangan* **langsung dari berkas JSON di atas**, sehingga logika yang diuji sama dengan yang berjalan di n8n. Yang ditiru hanya node bawaan n8n dan Gemini.

```
cd n8n\simulator
npm install
node n8n-tiruan.mjs
```

Simulator membaca `N8N_API_TOKEN`, `N8N_WEBHOOK_SECRET` dan `APP_URL` dari `.env` Laravel, lalu mendengarkan di `http://localhost:5678` (hentikan n8n sungguhan lebih dulu agar port tidak bentrok). Pengaturan lewat variabel lingkungan:

| Variabel | Fungsi |
|----------|--------|
| `LARAVEL_URL` | Alamat Laravel bila berbeda dari `APP_URL`. |
| `PORT` | Port simulator (bawaan 5678). |
| `GEMINI_API_KEY` | Bila diisi, memanggil **Gemini sungguhan** (dan `GEMINI_MODEL` untuk mengganti model). Cara cepat menguji kunci dan nama model tanpa n8n. |
| `SIMULASI_GEMINI_GAGAL=1` | Memaksa jalur *Jawaban Cadangan* (Gemini gagal). |

Tanpa `GEMINI_API_KEY`, "Gemini" tiruan menjawab dengan dua kalimat awal dari potongan materi teratas dan menandainya `[1]`. Ia **tidak menilai relevansi** (Gemini sungguhan yang memutuskan "tidak ditemukan"), jadi pertanyaan di luar materi bisa tetap dijawab dengan kutipan yang kurang cocok. Itu keterbatasan simulator, bukan Laravel.

Contoh bentuk keluaran di terminal simulator:

```
[indeks berkas 6] PDF diunduh (2565 byte)
[indeks berkas 6] status=terindeks bagian=2 -> Laravel: {"ok":true,"jumlah_bagian":2}
[tanya user 7] "Apa yang dimaksud dengan evaporasi?" -> 1 potongan materi
[tanya user 7] status=berhasil ditemukan=true sumber=1
```

## G. Pemecahan masalah

| Gejala | Periksa |
|--------|---------|
| Webhook n8n membalas 403 | Nilai kredensial *Rahasia Webhook* berbeda dengan `N8N_WEBHOOK_SECRET`. |
| Webhook membalas 404 | Workflow belum **Publish**, atau memakai URL `webhook-test` (hanya hidup saat *Listen for test event*). |
| *Ambil PDF* / *Cari Materi* galat 401 | Kredensial *Token Laravel* salah; nilainya harus diawali `Bearer ` dan token sama dengan `N8N_API_TOKEN`. |
| *Ambil PDF* galat koneksi | `laravel_url` tidak terjangkau dari n8n. Docker: jalankan dengan `--add-host=lms-sekolah.test:host-gateway` (lihat bagian A). |
| *Cari Materi* mengembalikan `data: []` | Bukan masalah Gemini: materi belum **Terbit**, berkas belum **Terindeks**, atau pertanyaan hanya berisi kata umum. |
| *Ekstrak Teks* menghasilkan bentuk lain | Periksa output node itu; bila field teks bernama lain, ubah hanya tiga baris pertama kode node *Potong Teks*. |
| Gemini 400 menyebut *schema* | Hapus blok `responseSchema` pada kode node *Susun Prompt* (biarkan `responseMimeType`). *Olah Jawaban* tetap bekerja. |
| Gemini 404 *model not found* | Ganti `model` di node *Konfigurasi* Workflow 2. |
| Gemini 429 | Kuota habis; coba lagi nanti. Siswa melihat pesan cadangan. |
