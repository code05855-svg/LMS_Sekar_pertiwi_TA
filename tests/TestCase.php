<?php

namespace Tests;

use App\Enums\StatusIndeks;
use App\Enums\StatusMateri;
use App\Models\BerkasMateri;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Materi;
use App\Models\PenugasanGuru;
use App\Models\PengumpulanTugas;
use App\Models\Tugas;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Dasar semua pengujian. Basis data dikosongkan per tes dengan TRUNCATE (bukan dibungkus
 * transaksi) karena indeks FULLTEXT InnoDB hanya melihat data yang sudah di-commit,
 * sehingga pencarian materi tidak teruji bila tiap tes berada di dalam transaksi.
 * Skema dimigrasi sekali per proses (migrate:fresh) pada database lms_sekolah_test.
 */
abstract class TestCase extends BaseTestCase
{
    private static bool $skemaSiap = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->siapkanBasisData();

        Storage::fake('materi');
        Storage::fake('pengumpulan');
    }

    private function siapkanBasisData(): void
    {
        if (! self::$skemaSiap) {
            Artisan::call('migrate:fresh', ['--force' => true]);
            self::$skemaSiap = true;

            return;
        }

        Schema::disableForeignKeyConstraints();

        foreach (Schema::getTableListing(schemaQualified: false) as $tabel) {
            if ($tabel !== 'migrations') {
                DB::table($tabel)->truncate();
            }
        }

        Schema::enableForeignKeyConstraints();
    }

    // ------------------------------------------------------------------
    // Pembuat data
    // ------------------------------------------------------------------

    protected function admin(array $atribut = []): User
    {
        return User::factory()->admin()->create($atribut);
    }

    protected function guru(array $atribut = []): User
    {
        return User::factory()->guru()->create($atribut);
    }

    protected function siswa(?Kelas $kelas = null, array $atribut = []): User
    {
        return User::factory()->siswa($kelas)->create($atribut);
    }

    protected function kelas(string $nama = 'VII A', string $tahun = '2026/2027'): Kelas
    {
        return Kelas::create([
            'tingkat' => strtok($nama, ' '),
            'nama' => $nama,
            'tahun_ajaran' => $tahun,
        ]);
    }

    protected function mapel(string $nama = 'IPA'): MataPelajaran
    {
        return MataPelajaran::create([
            'kode' => strtoupper(Str::substr($nama, 0, 3)).random_int(10, 99),
            'nama' => $nama,
        ]);
    }

    protected function penugasan(User $guru, Kelas $kelas, MataPelajaran $mapel): PenugasanGuru
    {
        return PenugasanGuru::create([
            'guru_id' => $guru->id,
            'kelas_id' => $kelas->id,
            'mata_pelajaran_id' => $mapel->id,
        ]);
    }

    protected function materi(PenugasanGuru $penugasan, array $atribut = []): Materi
    {
        return Materi::create($atribut + [
            'penugasan_guru_id' => $penugasan->id,
            'judul' => 'Fotosintesis',
            'deskripsi' => 'Proses tumbuhan membuat makanan.',
            'status' => StatusMateri::Terbit,
        ]);
    }

    /** Berkas PDF langsung tersimpan (tanpa memicu Observer/n8n). */
    protected function berkas(Materi $materi, array $atribut = []): BerkasMateri
    {
        $path = $materi->id.'/'.Str::uuid().'.pdf';
        Storage::disk('materi')->put($path, $this->isiPdf());

        return BerkasMateri::withoutEvents(fn () => BerkasMateri::create($atribut + [
            'materi_id' => $materi->id,
            'nama_asli' => 'fotosintesis.pdf',
            'path' => $path,
            'mime' => 'application/pdf',
            'ukuran' => 1200,
            'status_indeks' => StatusIndeks::Menunggu,
        ]));
    }

    protected function tugas(PenugasanGuru $penugasan, array $atribut = []): Tugas
    {
        return Tugas::create($atribut + [
            'penugasan_guru_id' => $penugasan->id,
            'judul' => 'Laporan pengamatan',
            'instruksi' => 'Amati tanaman di rumah.',
            'tenggat' => now()->addDays(3),
            'nilai_maksimum' => 100,
        ]);
    }

    protected function pengumpulan(Tugas $tugas, User $siswa, array $atribut = []): PengumpulanTugas
    {
        $path = $tugas->id.'/'.$siswa->id.'-'.Str::uuid().'.pdf';
        Storage::disk('pengumpulan')->put($path, $this->isiPdf());

        return PengumpulanTugas::create($atribut + [
            'tugas_id' => $tugas->id,
            'siswa_id' => $siswa->id,
            'nama_asli' => 'jawaban.pdf',
            'path' => $path,
            'mime' => 'application/pdf',
            'ukuran' => 1200,
            'waktu_kumpul' => now(),
            'status' => 'tepat_waktu',
        ]);
    }

    /** Ganti semua tiruan HTTP sebelumnya (Http::fake biasa memakai tiruan PERTAMA yang cocok). */
    protected function tiruHttp(array $tiruan): void
    {
        Http::swap(new HttpFactory());
        Http::fake($tiruan);
    }

    // ------------------------------------------------------------------
    // Berkas uji
    // ------------------------------------------------------------------

    protected function isiPdf(): string
    {
        return "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n";
    }

    protected function pdf(string $nama = 'materi.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($nama, $this->isiPdf());
    }

    /**
     * Satu sekolah kecil: dua kelas, dua mapel, dua guru, tiga siswa.
     *   guru1 mengajar IPA dan Matematika di VII A, guru2 mengajar IPA di VII B.
     *
     * @return object{admin:User, guru1:User, guru2:User, siswaA1:User, siswaA2:User, siswaB1:User, kelasA:Kelas, kelasB:Kelas, ipa:MataPelajaran, mtk:MataPelajaran, ipaA:PenugasanGuru, mtkA:PenugasanGuru, ipaB:PenugasanGuru}
     */
    protected function sekolah(): object
    {
        $s = new \stdClass();
        $s->admin = $this->admin(['nomor_induk' => 'admin']);
        $s->kelasA = $this->kelas('VII A');
        $s->kelasB = $this->kelas('VII B');
        $s->ipa = $this->mapel('IPA');
        $s->mtk = $this->mapel('Matematika');
        $s->guru1 = $this->guru(['nama' => 'Bu Rina']);
        $s->guru2 = $this->guru(['nama' => 'Pak Dodi']);
        $s->siswaA1 = $this->siswa($s->kelasA, ['nama' => 'Alya Putri']);
        $s->siswaA2 = $this->siswa($s->kelasA, ['nama' => 'Bima Saputra']);
        $s->siswaB1 = $this->siswa($s->kelasB, ['nama' => 'Citra Dewi']);
        $s->ipaA = $this->penugasan($s->guru1, $s->kelasA, $s->ipa);
        $s->mtkA = $this->penugasan($s->guru1, $s->kelasA, $s->mtk);
        $s->ipaB = $this->penugasan($s->guru2, $s->kelasB, $s->ipa);

        return $s;
    }
}
