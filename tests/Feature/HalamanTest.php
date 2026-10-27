<?php

namespace Tests\Feature;

use App\Enums\StatusChat;
use App\Enums\StatusIndeks;
use App\Enums\StatusMateri;
use App\Models\ForumBalasan;
use App\Models\ForumTopik;
use App\Models\RiwayatChat;
use Database\Seeders\FaqSeeder;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Smoke test: setiap halaman GET terbuka (200) untuk perannya dan tertutup untuk peran lain,
 * dengan data lengkap (termasuk keadaan gagal, terlambat, disematkan, dikunci).
 * LMS_CEGAH_LAZY_LOADING aktif di phpunit.xml sehingga N+1 langsung gagal.
 */
class HalamanTest extends TestCase
{
    private object $s;

    /** @var array<string, mixed> */
    private array $d = [];

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => FaqSeeder::class, '--force' => true]);
        $s = $this->s = $this->sekolah();

        $materi = $this->materi($s->ipaA, ['judul' => 'Fotosintesis']);
        $draf = $this->materi($s->ipaA, ['judul' => 'Materi draf', 'status' => StatusMateri::Draf]);
        $this->d['materi'] = $materi;
        $this->d['draf'] = $draf;
        $this->d['berkas'] = $this->berkas($materi, ['status_indeks' => StatusIndeks::Terindeks, 'jumlah_halaman' => 5]);
        $this->d['berkasGagal'] = $this->berkas($materi, ['status_indeks' => StatusIndeks::Gagal, 'pesan_galat' => 'PDF tidak berisi teks']);
        $this->berkas($materi, ['status_indeks' => StatusIndeks::Diproses]);

        $this->d['tugas'] = $tugas = $this->tugas($s->ipaA, ['judul' => 'Laporan']);
        $this->d['tugasLewat'] = $lewat = $this->tugas($s->ipaA, ['judul' => 'Tugas lampau', 'tenggat' => now()->subDays(2)]);
        $this->tugas($s->mtkA, ['judul' => 'Latihan soal']);
        $this->d['kumpul'] = $this->pengumpulan($tugas, $s->siswaA1);
        $this->d['dinilai'] = $this->pengumpulan($lewat, $s->siswaA1, ['status' => 'terlambat']);
        $this->d['dinilai']->beriNilai(88, 'Bagus sekali');
        $this->pengumpulan($lewat, $s->siswaA2);

        $this->d['topik'] = $topik = ForumTopik::create([
            'penugasan_guru_id' => $s->ipaA->id, 'user_id' => $s->siswaA1->id,
            'judul' => 'Apa itu klorofil?', 'isi' => 'Saya belum paham fungsi klorofil.',
        ]);
        $this->d['topikSemat'] = ForumTopik::create([
            'penugasan_guru_id' => $s->ipaA->id, 'user_id' => $s->guru1->id,
            'judul' => 'Pengumuman praktikum', 'isi' => 'Bawa tanaman.',
        ]);
        $this->d['topikSemat']->sematkan();
        $this->d['topikSemat']->kunci();
        $this->d['balasan'] = ForumBalasan::create(['forum_topik_id' => $topik->id, 'user_id' => $s->guru1->id, 'isi' => 'Klorofil menyerap cahaya.']);
        ForumBalasan::create(['forum_topik_id' => $topik->id, 'user_id' => $s->siswaA2->id, 'isi' => 'Terima kasih Bu.']);

        RiwayatChat::create([
            'user_id' => $s->siswaA1->id, 'sesi_id' => 'sesi-1', 'pertanyaan' => 'Apa itu fotosintesis?',
            'jawaban' => 'Proses membuat makanan.', 'status' => StatusChat::Berhasil, 'waktu_respons_ms' => 1500,
            'sumber' => [['no' => 1, 'materi_id' => $materi->id, 'materi' => 'Fotosintesis', 'mapel' => 'IPA', 'berkas' => 'fotosintesis.pdf', 'halaman' => 2]],
        ]);
        RiwayatChat::create([
            'user_id' => $s->siswaA1->id, 'sesi_id' => 'sesi-1', 'pertanyaan' => 'Pertanyaan gagal',
            'jawaban' => 'Maaf, chatbot sedang tidak dapat dihubungi.', 'status' => StatusChat::Gagal, 'sumber' => [],
        ]);
    }

    /** @return array<int, string> */
    private function urlAdmin(): array
    {
        $guru = $this->s->guru1;
        $siswa = $this->s->siswaA1;

        return [
            route('dashboard'), route('profil.edit'),
            route('admin.guru.index'), route('admin.guru.create'), route('admin.guru.edit', $guru),
            route('admin.siswa.index'), route('admin.siswa.create'), route('admin.siswa.edit', $siswa),
            route('admin.kelas.index'), route('admin.kelas.create'), route('admin.kelas.edit', $this->s->kelasA), route('admin.kelas.show', $this->s->kelasA),
            route('admin.mata-pelajaran.index'), route('admin.mata-pelajaran.create'), route('admin.mata-pelajaran.edit', $this->s->ipa),
            route('admin.penugasan-guru.index'), route('admin.penugasan-guru.create'),
            route('admin.penempatan-siswa.index'), route('admin.penempatan-siswa.index', ['kelas' => $this->s->kelasA->id]),
            route('admin.guru.index', ['cari' => 'rina']), route('admin.siswa.index', ['cari' => 'alya']),
        ];
    }

    /** @return array<int, string> */
    private function urlGuru(): array
    {
        $d = $this->d;

        return [
            route('dashboard'), route('profil.edit'),
            route('guru.kelas.index'), route('guru.kelas.show', $this->s->ipaA),
            route('guru.materi.index'), route('guru.materi.index', ['status' => 'draf', 'cari' => 'foto', 'penugasan' => $this->s->ipaA->id]),
            route('guru.materi.create'), route('guru.materi.create', ['penugasan' => $this->s->ipaA->id]),
            route('guru.materi.show', $d['materi']), route('guru.materi.edit', $d['materi']), route('guru.materi.show', $d['draf']),
            route('guru.berkas.show', $d['berkas']),
            route('guru.tugas.index'), route('guru.tugas.create'), route('guru.tugas.show', $d['tugas']), route('guru.tugas.edit', $d['tugas']),
            route('guru.pengumpulan-tugas.index', $d['tugas']), route('guru.pengumpulan-tugas.index', $d['tugasLewat']),
            route('guru.pengumpulan-tugas.show', $d['kumpul']), route('guru.pengumpulan-tugas.show', $d['dinilai']),
            route('guru.pengumpulan-tugas.unduh', $d['kumpul']),
            route('forum.index'), route('forum.topik.index', $this->s->ipaA), route('forum.topik.index', [$this->s->ipaA, 'cari' => 'klorofil']),
            route('forum.topik.create', $this->s->ipaA), route('forum.topik.show', $d['topik']), route('forum.topik.show', $d['topikSemat']),
            route('chatbot.index'), route('chatbot.index', ['fragmen' => 1]), route('chatbot.riwayat'),
        ];
    }

    /** @return array<int, string> */
    private function urlSiswa(): array
    {
        $d = $this->d;

        return [
            route('dashboard'), route('profil.edit'),
            route('siswa.mata-pelajaran.index'), route('siswa.mata-pelajaran.show', $this->s->ipaA),
            route('siswa.materi.show', $d['materi']), route('siswa.materi.unduh', $d['berkas']), route('siswa.materi.unduh', [$d['berkas'], 'lihat' => 1]),
            route('siswa.tugas.show', $d['tugas']), route('siswa.tugas.show', $d['tugasLewat']),
            route('siswa.pengumpulan-tugas.unduh', $d['kumpul']),
            route('siswa.nilai.index'),
            route('forum.index'), route('forum.topik.index', $this->s->ipaA), route('forum.topik.create', $this->s->ipaA),
            route('forum.topik.show', $d['topik']), route('forum.topik.edit', $d['topik']), route('forum.topik.show', $d['topikSemat']),
            route('chatbot.index'), route('chatbot.index', ['fragmen' => 1]), route('chatbot.riwayat'),
        ];
    }

    #[Test]
    public function semua_halaman_admin_terbuka(): void
    {
        $this->actingAs($this->s->admin);

        foreach ($this->urlAdmin() as $url) {
            $this->get($url)->assertOk();
        }
    }

    #[Test]
    public function semua_halaman_guru_terbuka(): void
    {
        $this->actingAs($this->s->guru1);

        foreach ($this->urlGuru() as $url) {
            $this->get($url)->assertOk();
        }
    }

    #[Test]
    public function semua_halaman_siswa_terbuka(): void
    {
        $this->actingAs($this->s->siswaA1);

        foreach ($this->urlSiswa() as $url) {
            $this->get($url)->assertOk();
        }
    }

    #[Test]
    public function dashboard_kosong_tetap_terbuka_untuk_semua_peran(): void
    {
        foreach ([$this->admin(), $this->guru(), $this->siswa()] as $pengguna) {
            $this->actingAs($pengguna)->get(route('dashboard'))->assertOk();
        }
    }

    #[Test]
    public function halaman_siswa_tanpa_kelas_tidak_error(): void
    {
        $this->actingAs($this->siswa());

        foreach ([route('siswa.mata-pelajaran.index'), route('siswa.nilai.index'), route('forum.index'), route('chatbot.index')] as $url) {
            $this->get($url)->assertOk();
        }
    }

    #[Test]
    public function tamu_diarahkan_ke_login(): void
    {
        foreach ([...$this->urlAdmin(), ...$this->urlGuru(), ...$this->urlSiswa()] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    #[Test]
    public function admin_tidak_boleh_membuka_halaman_guru_siswa_forum_chatbot(): void
    {
        $this->actingAs($this->s->admin);

        foreach (array_diff([...$this->urlGuru(), ...$this->urlSiswa()], $this->urlAdmin()) as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    #[Test]
    public function guru_dan_siswa_tidak_boleh_membuka_halaman_admin(): void
    {
        foreach ([$this->s->guru1, $this->s->siswaA1] as $pengguna) {
            $this->actingAs($pengguna);

            foreach (array_diff($this->urlAdmin(), [route('dashboard'), route('profil.edit')]) as $url) {
                $this->get($url)->assertForbidden();
            }
        }
    }

    #[Test]
    public function guru_tidak_boleh_membuka_halaman_khusus_siswa_dan_sebaliknya(): void
    {
        $this->actingAs($this->s->guru1);
        $this->get(route('siswa.mata-pelajaran.index'))->assertForbidden();
        $this->get(route('siswa.nilai.index'))->assertForbidden();

        $this->actingAs($this->s->siswaA1);
        $this->get(route('guru.kelas.index'))->assertForbidden();
        $this->get(route('guru.materi.create'))->assertForbidden();
        $this->get(route('guru.tugas.index'))->assertForbidden();
    }

    #[Test]
    public function siswa_kelas_lain_tidak_boleh_membuka_materi_tugas_forum_kelas_ini(): void
    {
        $this->actingAs($this->s->siswaB1);

        $this->get(route('siswa.mata-pelajaran.show', $this->s->ipaA))->assertForbidden();
        $this->get(route('siswa.materi.show', $this->d['materi']))->assertForbidden();
        $this->get(route('siswa.materi.unduh', $this->d['berkas']))->assertForbidden();
        $this->get(route('siswa.tugas.show', $this->d['tugas']))->assertForbidden();
        $this->get(route('siswa.pengumpulan-tugas.unduh', $this->d['kumpul']))->assertForbidden();
        $this->get(route('forum.topik.index', $this->s->ipaA))->assertForbidden();
        $this->get(route('forum.topik.show', $this->d['topik']))->assertForbidden();
    }

    #[Test]
    public function siswa_tidak_boleh_membuka_materi_draf(): void
    {
        $this->actingAs($this->s->siswaA1);

        $this->get(route('siswa.materi.show', $this->d['draf']))->assertForbidden();
    }

    #[Test]
    public function guru_lain_tidak_boleh_membuka_materi_tugas_kelas_ini(): void
    {
        $this->actingAs($this->s->guru2);

        $this->get(route('guru.kelas.show', $this->s->ipaA))->assertForbidden();
        $this->get(route('guru.materi.show', $this->d['materi']))->assertForbidden();
        $this->get(route('guru.materi.edit', $this->d['materi']))->assertForbidden();
        $this->get(route('guru.berkas.show', $this->d['berkas']))->assertForbidden();
        $this->get(route('guru.tugas.show', $this->d['tugas']))->assertForbidden();
        $this->get(route('guru.pengumpulan-tugas.index', $this->d['tugas']))->assertForbidden();
        $this->get(route('guru.pengumpulan-tugas.show', $this->d['kumpul']))->assertForbidden();
        $this->get(route('guru.pengumpulan-tugas.unduh', $this->d['kumpul']))->assertForbidden();
        $this->get(route('forum.topik.show', $this->d['topik']))->assertForbidden();
    }

    #[Test]
    public function siswa_hanya_bisa_mengunduh_jawaban_miliknya(): void
    {
        $this->actingAs($this->s->siswaA2);

        $this->get(route('siswa.pengumpulan-tugas.unduh', $this->d['kumpul']))->assertForbidden();
    }

    #[Test]
    public function halaman_tidak_ada_menampilkan_404_ramah(): void
    {
        $this->actingAs($this->s->guru1);

        $this->get('/tidak-ada')->assertNotFound();
        $this->get(route('guru.materi.show', 99999))->assertNotFound();
    }

    #[Test]
    public function parameter_filter_berbentuk_array_tidak_menjadi_galat_500(): void
    {
        $this->actingAs($this->s->admin);
        foreach (['admin.guru.index', 'admin.siswa.index', 'admin.mata-pelajaran.index', 'admin.kelas.index', 'admin.penugasan-guru.index', 'admin.penempatan-siswa.index'] as $rute) {
            $this->get(route($rute, ['cari' => ['x'], 'status' => ['y'], 'kelas' => ['1'], 'guru' => ['2'], 'tahun_ajaran' => ['z']]))->assertOk();
        }

        $this->actingAs($this->s->guru1);
        $this->get(route('guru.materi.index', ['cari' => ['x'], 'status' => ['draf'], 'penugasan' => ['1']]))->assertOk();
        $this->get(route('guru.tugas.index', ['cari' => ['x'], 'penugasan' => ['1']]))->assertOk();
        $this->get(route('forum.topik.index', [$this->s->ipaA, 'cari' => ['x']]))->assertOk();
        $this->get(route('guru.materi.create', ['penugasan' => ['1']]))->assertOk();
        $this->get(route('chatbot.index', ['fragmen' => ['1']]))->assertOk();
    }

    #[Test]
    public function isian_formulir_berbentuk_array_ditolak_validasi_bukan_500(): void
    {
        $this->actingAs($this->s->admin);
        $this->post(route('admin.mata-pelajaran.store'), ['kode' => ['A'], 'nama' => ['B']])->assertSessionHasErrors();
        $this->post(route('admin.kelas.store'), ['tingkat' => ['VII'], 'nama' => ['A'], 'tahun_ajaran' => ['2026/2027']])->assertSessionHasErrors();
        $this->post(route('admin.penugasan-guru.store'), ['guru_id' => ['1'], 'kelas_id' => ['1'], 'mata_pelajaran_id' => ['1']])->assertSessionHasErrors();
        $this->post(route('admin.guru.store'), ['nomor_induk' => ['x'], 'nama' => ['y'], 'password' => ['zzzzzzzzz']])->assertSessionHasErrors();
        $this->post(route('admin.penempatan-siswa.store'), ['kelas_id' => ['1'], 'siswa_ids' => 'bukan-array'])->assertSessionHasErrors();

        $this->actingAs($this->s->guru1);
        $this->post(route('guru.materi.store'), ['penugasan_guru_id' => ['1'], 'judul' => ['x'], 'status' => ['terbit']])->assertSessionHasErrors();
        $this->post(route('guru.tugas.store'), ['penugasan_guru_id' => ['1'], 'judul' => ['x'], 'tenggat' => ['2026-01-01'], 'nilai_maksimum' => ['1']])->assertSessionHasErrors();
        $this->put(route('guru.penilaian.update', $this->d['kumpul']), ['nilai' => ['90'], 'komentar' => ['x']])->assertSessionHasErrors();

        $this->actingAs($this->s->siswaA1);
        $this->post(route('forum.topik.store', $this->s->ipaA), ['judul' => ['x'], 'isi' => ['y']])->assertSessionHasErrors();
        $this->post(route('forum.balasan.store', $this->d['topik']), ['isi' => ['y']])->assertSessionHasErrors();

        $this->post(route('logout'));
        $this->post(route('login.store'), ['nomor_induk' => ['x'], 'password' => ['y']])->assertSessionHasErrors();
    }
}
