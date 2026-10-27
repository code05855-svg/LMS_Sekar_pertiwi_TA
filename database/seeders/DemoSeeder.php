<?php

namespace Database\Seeders;

use App\Enums\PeranPengguna;
use App\Enums\StatusIndeks;
use App\Enums\StatusMateri;
use App\Models\BagianMateri;
use App\Models\BerkasMateri;
use App\Models\ForumBalasan;
use App\Models\ForumTopik;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Materi;
use App\Models\PengumpulanTugas;
use App\Models\PenugasanGuru;
use App\Models\Tugas;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Data contoh untuk demo dan pengujian manual:
 *   php artisan db:seed --class=DemoSeeder
 *
 * Membuat 2 kelas, 6 mata pelajaran, 5 guru, 10 siswa (password semuanya: password123),
 * materi PDF yang SUDAH terindeks (teksnya dimuat dari database/seeders/demo/*.json,
 * jadi chatbot dapat menjawab begitu workflow n8n tanya-jawab aktif), tugas, nilai, dan forum.
 * Hanya berjalan bila belum ada akun guru, supaya data asli tidak tertimpa.
 */
class DemoSeeder extends Seeder
{
    private const PASSWORD = 'password123';

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('DemoSeeder tidak dijalankan di APP_ENV=production (password contoh diketahui umum).');

            return;
        }

        $this->call([AdminSeeder::class, FaqSeeder::class]);

        if (User::where('peran', PeranPengguna::Guru)->exists() || Kelas::exists()) {
            $this->command?->warn('Data contoh dilewati: sudah ada kelas atau akun guru di database.');

            return;
        }

        DB::transaction(function () {
            $hash = Hash::make(self::PASSWORD);
            $tahun = (now()->month >= 7 ? now()->year : now()->year - 1);
            $tahunAjaran = $tahun.'/'.($tahun + 1);

            // --- kelas dan mata pelajaran
            $kelasA = Kelas::create(['tingkat' => 'VII', 'nama' => 'VII A', 'tahun_ajaran' => $tahunAjaran]);
            $kelasB = Kelas::create(['tingkat' => 'VII', 'nama' => 'VII B', 'tahun_ajaran' => $tahunAjaran]);

            $mapel = collect([
                'MTK' => 'Matematika', 'BIN' => 'Bahasa Indonesia', 'IPA' => 'IPA',
                'BIG' => 'Bahasa Inggris', 'IPS' => 'IPS', 'INF' => 'Informatika',
            ])->map(fn ($nama, $kode) => MataPelajaran::create(['kode' => $kode, 'nama' => $nama]));

            // --- guru
            $akun = fn (string $nomor, string $nama, PeranPengguna $peran, ?Kelas $kelas = null) => User::create([
                'nomor_induk' => $nomor, 'nama' => $nama, 'password' => $hash, 'peran' => $peran, 'kelas_id' => $kelas?->id, 'aktif' => true,
            ]);

            $budi = $akun('guru01', 'Budi Santoso, S.Pd', PeranPengguna::Guru);
            $siti = $akun('guru02', 'Siti Rahmawati, S.Pd', PeranPengguna::Guru);
            $dewi = $akun('guru03', 'Dewi Lestari, S.Si', PeranPengguna::Guru);
            $andi = $akun('guru04', 'Andi Pratama, S.Pd', PeranPengguna::Guru);
            $rina = $akun('guru05', 'Rina Kusuma, S.Kom', PeranPengguna::Guru);

            $tugaskan = fn (User $guru, Kelas $kelas, string $kode) => PenugasanGuru::create([
                'guru_id' => $guru->id, 'kelas_id' => $kelas->id, 'mata_pelajaran_id' => $mapel[$kode]->id,
            ]);

            $mtkA = $tugaskan($budi, $kelasA, 'MTK');
            $tugaskan($budi, $kelasB, 'MTK');
            $binA = $tugaskan($siti, $kelasA, 'BIN');
            $tugaskan($siti, $kelasB, 'BIN');
            $ipaA = $tugaskan($dewi, $kelasA, 'IPA');
            $ipaB = $tugaskan($dewi, $kelasB, 'IPA');
            $bigA = $tugaskan($andi, $kelasA, 'BIG');
            $tugaskan($andi, $kelasA, 'IPS');
            $tugaskan($rina, $kelasA, 'INF');

            // --- siswa
            $namaA = ['Alya Putri', 'Bima Saputra', 'Citra Dewi', 'Dimas Pratama', 'Eka Fitriani', 'Fajar Ramadhan'];
            $namaB = ['Gita Maharani', 'Hadi Wijaya', 'Intan Permata', 'Joko Susilo'];
            $siswa = [];
            foreach ($namaA as $i => $nama) {
                $siswa[$nama] = $akun('2026'.str_pad($i + 1, 3, '0', STR_PAD_LEFT), $nama, PeranPengguna::Siswa, $kelasA);
            }
            foreach ($namaB as $i => $nama) {
                $siswa[$nama] = $akun('2026'.str_pad($i + 7, 3, '0', STR_PAD_LEFT), $nama, PeranPengguna::Siswa, $kelasB);
            }

            // --- materi dengan PDF yang sudah terindeks
            $this->materiPdf($ipaA, 'fotosintesis', 'Fotosintesis pada Tumbuhan', 'Proses tumbuhan hijau membuat makanan dengan bantuan cahaya matahari: bahan, hasil, dan faktor yang memengaruhinya.');
            $plsv = $this->materiPdf($mtkA, 'plsv', 'Persamaan Linear Satu Variabel', 'Konsep dasar PLSV dan langkah penyelesaiannya dengan operasi yang setara.');
            $this->materiPdf($binA, 'teks-eksplanasi', 'Teks Eksplanasi', 'Struktur, ciri kebahasaan, dan contoh teks eksplanasi.');
            $this->materiPdf($bigA, 'descriptive-text', 'Descriptive Text', 'Learn to describe people, places, and objects.');
            $this->materiPdf($ipaB, 'organisasi-kehidupan', 'Sistem Organisasi Kehidupan', 'Mengenal sel, jaringan, organ, dan sistem organ.');
            Materi::create([
                'penugasan_guru_id' => $ipaA->id, 'judul' => 'Ekosistem (draf)', 'status' => StatusMateri::Draf,
                'deskripsi' => 'Masih disusun, belum terlihat oleh siswa.',
            ]);

            // --- tugas, pengumpulan, dan nilai
            $latihan = Tugas::create(['penugasan_guru_id' => $mtkA->id, 'judul' => 'Latihan Persamaan Linear', 'instruksi' => "Kerjakan soal latihan 1 sampai 4 pada materi PLSV.\nTulis langkah penyelesaiannya, lalu unggah dalam satu berkas PDF atau foto.", 'tenggat' => now()->addDays(3)->setTime(23, 59), 'nilai_maksimum' => 100]);
            $ceritaPlsv = Tugas::create(['penugasan_guru_id' => $mtkA->id, 'judul' => 'Soal Cerita PLSV', 'instruksi' => 'Buat dua soal cerita tentang PLSV beserta penyelesaiannya.', 'tenggat' => now()->subDays(2)->setTime(23, 59), 'nilai_maksimum' => 100]);
            Tugas::create(['penugasan_guru_id' => $ipaA->id, 'judul' => 'Laporan Pengamatan Fotosintesis', 'instruksi' => 'Amati satu tanaman di sekitar rumah selama tiga hari, lalu tulis laporan singkat dengan foto.', 'tenggat' => now()->addDays(5)->setTime(23, 59), 'nilai_maksimum' => 100]);
            Tugas::create(['penugasan_guru_id' => $binA->id, 'judul' => 'Analisis Teks Eksplanasi', 'instruksi' => 'Tentukan struktur dan kata hubung kausalitas pada teks "Terjadinya Hujan".', 'tenggat' => now()->addDays(2)->setTime(23, 59), 'nilai_maksimum' => 100]);
            Tugas::create(['penugasan_guru_id' => $bigA->id, 'judul' => 'Descriptive Text: My Best Friend', 'instruksi' => 'Write a descriptive text (5 sentences) about your best friend.', 'tenggat' => now()->addDays(6)->setTime(23, 59), 'nilai_maksimum' => 100]);

            $kumpul = function (Tugas $tugas, User $murid, $waktu, ?float $nilai = null, ?string $komentar = null) {
                $path = $tugas->id.'/'.$murid->id.'-'.Str::uuid().'.pdf';
                Storage::disk('pengumpulan')->put($path, file_get_contents(__DIR__.'/demo/plsv.pdf'));

                $p = PengumpulanTugas::create([
                    'tugas_id' => $tugas->id, 'siswa_id' => $murid->id,
                    'nama_asli' => 'jawaban-'.Str::slug($murid->nama).'.pdf', 'path' => $path, 'mime' => 'application/pdf',
                    'ukuran' => Storage::disk('pengumpulan')->size($path), 'waktu_kumpul' => $waktu,
                    'status' => $tugas->statusUntuk($waktu),
                ]);

                if ($nilai !== null) {
                    $p->beriNilai($nilai, $komentar);
                }
            };

            $kumpul($ceritaPlsv, $siswa['Alya Putri'], $ceritaPlsv->tenggat->copy()->subDay(), 92, 'Soal ceritamu kreatif dan langkahnya runtut. Pertahankan!');
            $kumpul($ceritaPlsv, $siswa['Bima Saputra'], $ceritaPlsv->tenggat->copy()->addHours(5), 78, 'Jawaban benar, tetapi dikumpulkan terlambat. Periksa lagi langkah ke-3.');
            $kumpul($ceritaPlsv, $siswa['Citra Dewi'], $ceritaPlsv->tenggat->copy()->subHours(3));
            $kumpul($latihan, $siswa['Alya Putri'], now()->subHours(6));

            // --- forum
            $sematan = ForumTopik::create(['penugasan_guru_id' => $mtkA->id, 'user_id' => $budi->id, 'judul' => 'Aturan pengumpulan tugas Matematika', 'isi' => "Kumpulkan tugas dalam bentuk PDF atau foto yang jelas.\nTugas yang dikumpulkan setelah tenggat tetap diterima, tetapi tercatat terlambat."]);
            $sematan->sematkan();

            $topik = ForumTopik::create(['penugasan_guru_id' => $mtkA->id, 'user_id' => $siswa['Alya Putri']->id, 'judul' => 'Bagaimana cara menyelesaikan PLSV?', 'isi' => 'Saya masih bingung kenapa kedua ruas harus ditambah atau dikurangi bilangan yang sama. Bisa dijelaskan, Pak?']);
            ForumBalasan::create(['forum_topik_id' => $topik->id, 'user_id' => $budi->id, 'isi' => 'Supaya kesetaraan persamaan tetap terjaga. Bayangkan timbangan: jika satu sisi ditambah beban, sisi lain harus ditambah beban yang sama agar tetap seimbang.']);
            ForumBalasan::create(['forum_topik_id' => $topik->id, 'user_id' => $siswa['Bima Saputra']->id, 'isi' => 'Aku juga baru paham setelah baca contoh 2x + 3 = 11 di materi halaman 2.']);

            $praktikum = ForumTopik::create(['penugasan_guru_id' => $ipaA->id, 'user_id' => $siswa['Bima Saputra']->id, 'judul' => 'Pertanyaan tentang laporan praktikum', 'isi' => 'Apakah laporan pengamatan fotosintesis boleh memakai foto dari ponsel?']);
            ForumBalasan::create(['forum_topik_id' => $praktikum->id, 'user_id' => $dewi->id, 'isi' => 'Boleh, asalkan fotonya jelas dan menunjukkan tanggal pengamatan.']);
        });

        $this->command?->info('Data contoh dibuat. Login: admin / (ADMIN_PASSWORD), guru01..guru05 atau 2026001..2026010, password: '.self::PASSWORD);
    }

    /** Salin PDF contoh ke disk materi dan muat potongan teksnya sebagai indeks chatbot. */
    private function materiPdf(PenugasanGuru $penugasan, string $kode, string $judul, string $deskripsi): Materi
    {
        $materi = Materi::create([
            'penugasan_guru_id' => $penugasan->id, 'judul' => $judul, 'deskripsi' => $deskripsi, 'status' => StatusMateri::Terbit,
        ]);

        $path = $materi->id.'/'.Str::uuid().'.pdf';
        $isi = file_get_contents(__DIR__."/demo/{$kode}.pdf");
        Storage::disk('materi')->put($path, $isi);
        $sidecar = json_decode(file_get_contents(__DIR__."/demo/{$kode}.json"), true);

        // Observer dimatikan: data contoh tidak perlu memanggil n8n.
        $berkas = BerkasMateri::withoutEvents(fn () => BerkasMateri::create([
            'materi_id' => $materi->id, 'nama_asli' => $kode.'.pdf', 'path' => $path, 'mime' => 'application/pdf',
            'ukuran' => strlen($isi), 'jumlah_halaman' => count($sidecar['halaman']),
            'status_indeks' => StatusIndeks::Terindeks, 'diindeks_pada' => now(),
        ]));

        $nomor = 0;
        foreach ($sidecar['halaman'] as $halaman) {
            $paragraf = preg_split("/\n{2,}/", $halaman['teks']);
            $judulHalaman = array_shift($paragraf); // subjudul halaman menempel ke paragraf pertama

            foreach ($paragraf as $i => $potongan) {
                $teks = trim(($i === 0 ? $judulHalaman."\n" : '').$potongan);

                BagianMateri::create(['berkas_materi_id' => $berkas->id, 'nomor_bagian' => $nomor++, 'halaman' => $halaman['halaman'], 'teks' => $teks]);
            }
        }

        return $materi;
    }
}
