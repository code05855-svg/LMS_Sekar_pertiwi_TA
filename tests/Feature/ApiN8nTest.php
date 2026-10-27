<?php

namespace Tests\Feature;

use App\Enums\StatusIndeks;
use App\Enums\StatusMateri;
use App\Models\BagianMateri;
use App\Models\BerkasMateri;
use App\Models\Faq;
use App\Models\Materi;
use Database\Seeders\FaqSeeder;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Kontrak /api/n8n/* yang dipakai workflow n8n. */
class ApiN8nTest extends TestCase
{
    private object $s;

    protected function setUp(): void
    {
        parent::setUp();

        $this->s = $this->sekolah();
    }

    /** @return array<string, string> */
    private function token(): array
    {
        return ['Authorization' => 'Bearer token-uji', 'Accept' => 'application/json'];
    }

    private function bagian(BerkasMateri $berkas, array $teks): void
    {
        foreach ($teks as $i => $isi) {
            BagianMateri::create(['berkas_materi_id' => $berkas->id, 'nomor_bagian' => $i, 'halaman' => $i + 1, 'teks' => $isi]);
        }
    }

    /** Berkas terindeks milik materi $materi dengan potongan teks tertentu. */
    private function terindeks(Materi $materi, array $teks): BerkasMateri
    {
        $berkas = $this->berkas($materi, ['status_indeks' => StatusIndeks::Terindeks]);
        $this->bagian($berkas, $teks);

        return $berkas;
    }

    // ------------------------------------------------------------- otorisasi

    #[Test]
    public function semua_rute_api_menolak_permintaan_tanpa_atau_dengan_token_salah(): void
    {
        $berkas = $this->berkas($this->materi($this->s->ipaA));
        $rute = [
            ['get', route('api.n8n.berkas.show', $berkas)],
            ['post', route('api.n8n.bagian.store', $berkas)],
            ['delete', route('api.n8n.bagian.destroy', $berkas)],
            ['get', route('api.n8n.pencarian.index', ['user_id' => $this->s->siswaA1->id, 'q' => 'klorofil'])],
            ['get', route('api.n8n.faq.index')],
        ];

        foreach ($rute as [$metode, $url]) {
            $this->json($metode, $url)->assertUnauthorized();
            $this->json($metode, $url, [], ['Authorization' => 'Bearer salah'])->assertUnauthorized();
            $this->json($metode, $url, [], ['Authorization' => 'Basic token-uji'])->assertUnauthorized();
        }
    }

    #[Test]
    public function sesi_login_biasa_tidak_membuka_api_n8n(): void
    {
        $this->actingAs($this->s->admin)->getJson(route('api.n8n.faq.index'))->assertUnauthorized();
    }

    #[Test]
    public function token_kosong_di_env_menutup_api_sepenuhnya(): void
    {
        config(['services.n8n.token' => '']);

        $this->getJson(route('api.n8n.faq.index'), ['Authorization' => 'Bearer '])->assertUnauthorized();
        $this->getJson(route('api.n8n.faq.index'))->assertUnauthorized();
    }

    // --------------------------------------------------------- Workflow 1

    #[Test]
    public function n8n_mengunduh_pdf_berkas(): void
    {
        $berkas = $this->berkas($this->materi($this->s->ipaA));

        $respons = $this->get(route('api.n8n.berkas.show', $berkas), $this->token());

        $respons->assertOk();
        $this->assertStringStartsWith('%PDF', $respons->streamedContent());
        $this->getJson(route('api.n8n.berkas.show', 99999), $this->token())->assertNotFound();
    }

    #[Test]
    public function n8n_menyimpan_hasil_ekstraksi_dan_berkas_menjadi_terindeks(): void
    {
        $berkas = $this->berkas($this->materi($this->s->ipaA), ['status_indeks' => StatusIndeks::Diproses]);

        $this->postJson(route('api.n8n.bagian.store', $berkas), [
            'status' => 'terindeks',
            'jumlah_halaman' => 2,
            'bagian' => [
                ['nomor_bagian' => 0, 'halaman' => 1, 'teks' => 'Klorofil menyerap cahaya matahari.'],
                ['nomor_bagian' => 1, 'halaman' => 2, 'teks' => 'Stomata mengatur pertukaran gas.'],
            ],
        ], $this->token())->assertOk()->assertJson(['ok' => true, 'jumlah_bagian' => 2]);

        $berkas->refresh();
        $this->assertSame(StatusIndeks::Terindeks, $berkas->status_indeks);
        $this->assertSame(2, $berkas->jumlah_halaman);
        $this->assertNotNull($berkas->diindeks_pada);
        $this->assertSame(2, $berkas->bagianMateri()->count());
    }

    #[Test]
    public function mengirim_ulang_hasil_mengganti_bagian_lama_bukan_menggandakan(): void
    {
        $berkas = $this->berkas($this->materi($this->s->ipaA));
        $isi = fn (string $teks) => ['status' => 'terindeks', 'bagian' => [['nomor_bagian' => 0, 'halaman' => 1, 'teks' => $teks]]];

        $this->postJson(route('api.n8n.bagian.store', $berkas), $isi('versi lama'), $this->token())->assertOk();
        $this->postJson(route('api.n8n.bagian.store', $berkas), $isi('versi baru'), $this->token())->assertOk();

        $this->assertSame(['versi baru'], $berkas->bagianMateri()->pluck('teks')->all());
    }

    #[Test]
    public function n8n_melaporkan_kegagalan_ekstraksi_dan_indeks_lama_dibuang(): void
    {
        $berkas = $this->terindeks($this->materi($this->s->ipaA), ['teks lama']);

        $this->postJson(route('api.n8n.bagian.store', $berkas), ['status' => 'gagal', 'pesan' => 'PDF tidak berisi teks (hasil scan)'], $this->token())->assertOk();

        $berkas->refresh();
        $this->assertSame(StatusIndeks::Gagal, $berkas->status_indeks);
        $this->assertSame('PDF tidak berisi teks (hasil scan)', $berkas->pesan_galat);
        $this->assertSame(0, $berkas->bagianMateri()->count());
    }

    #[Test]
    public function data_ekstraksi_yang_tidak_valid_ditolak_dan_indeks_lama_tetap_utuh(): void
    {
        $berkas = $this->terindeks($this->materi($this->s->ipaA), ['teks lama']);

        $this->postJson(route('api.n8n.bagian.store', $berkas), ['status' => 'terindeks'], $this->token())->assertUnprocessable();
        $this->postJson(route('api.n8n.bagian.store', $berkas), ['status' => 'menunggu'], $this->token())->assertUnprocessable();
        $this->postJson(route('api.n8n.bagian.store', $berkas), ['status' => 'terindeks', 'bagian' => [['nomor_bagian' => 0, 'teks' => '']]], $this->token())->assertUnprocessable();

        $this->assertSame(1, $berkas->bagianMateri()->count());
        $this->assertSame(StatusIndeks::Terindeks, $berkas->fresh()->status_indeks);
    }

    #[Test]
    public function bagian_dalam_jumlah_besar_tersimpan_utuh(): void
    {
        $berkas = $this->berkas($this->materi($this->s->ipaA));
        $bagian = collect(range(0, 449))->map(fn ($i) => ['nomor_bagian' => $i, 'halaman' => intdiv($i, 3) + 1, 'teks' => "Potongan nomor {$i}"])->all();

        $this->postJson(route('api.n8n.bagian.store', $berkas), ['status' => 'terindeks', 'bagian' => $bagian], $this->token())
            ->assertOk()->assertJson(['jumlah_bagian' => 450]);
    }

    #[Test]
    public function n8n_dapat_mengosongkan_indeks_berkas(): void
    {
        $berkas = $this->terindeks($this->materi($this->s->ipaA), ['a', 'b']);

        $this->deleteJson(route('api.n8n.bagian.destroy', $berkas), [], $this->token())->assertOk();

        $this->assertSame(0, $berkas->bagianMateri()->count());
        $this->assertSame(StatusIndeks::Menunggu, $berkas->fresh()->status_indeks);
    }

    // --------------------------------------------------------- Workflow 2

    private function cari(\App\Models\User $pengguna, string $q, array $tambahan = []): \Illuminate\Testing\TestResponse
    {
        return $this->getJson(route('api.n8n.pencarian.index', ['user_id' => $pengguna->id, 'q' => $q] + $tambahan), $this->token());
    }

    #[Test]
    public function pencarian_menemukan_bagian_yang_cocok_dengan_sumber_lengkap(): void
    {
        $materi = $this->materi($this->s->ipaA, ['judul' => 'Fotosintesis']);
        $berkas = $this->terindeks($materi, [
            'Klorofil adalah pigmen hijau yang menyerap cahaya matahari untuk fotosintesis.',
            'Stomata mengatur pertukaran gas pada daun.',
        ]);

        $respons = $this->cari($this->s->siswaA1, 'Apa fungsi klorofil pada tumbuhan?')->assertOk();

        $respons->assertJsonCount(1, 'data')->assertJsonPath('data.0.materi', 'Fotosintesis')
            ->assertJsonPath('data.0.mapel', 'IPA')
            ->assertJsonPath('data.0.berkas_id', $berkas->id)
            ->assertJsonPath('data.0.materi_id', $materi->id)
            ->assertJsonPath('data.0.halaman', 1);
        $this->assertStringContainsString('Klorofil', $respons->json('data.0.teks'));
        $this->assertGreaterThan(0, $respons->json('data.0.skor'));
    }

    #[Test]
    public function pencarian_mengurutkan_dari_skor_tertinggi_dan_menghormati_limit(): void
    {
        $this->terindeks($this->materi($this->s->ipaA), [
            'Klorofil ada di daun.',
            'Klorofil klorofil klorofil adalah pigmen utama klorofil.',
            'Bab lain tentang sel.',
        ]);

        $hasil = $this->cari($this->s->siswaA1, 'klorofil')->assertJsonCount(2, 'data')->json('data');
        $this->assertGreaterThan($hasil[1]['skor'], $hasil[0]['skor']);

        $this->cari($this->s->siswaA1, 'klorofil', ['limit' => 1])->assertJsonCount(1, 'data');
        $this->cari($this->s->siswaA1, 'klorofil', ['limit' => 99])->assertUnprocessable();
    }

    #[Test]
    public function siswa_tidak_pernah_mendapat_materi_kelas_lain(): void
    {
        $this->terindeks($this->materi($this->s->ipaB, ['judul' => 'Rahasia Kelas B']), ['Klorofil rahasia kelas B.']);

        $this->cari($this->s->siswaA1, 'klorofil')->assertOk()->assertJsonCount(0, 'data');
        $this->cari($this->s->siswaB1, 'klorofil')->assertJsonCount(1, 'data');
    }

    #[Test]
    public function materi_draf_dan_berkas_belum_terindeks_tidak_ikut_dicari(): void
    {
        $this->terindeks($this->materi($this->s->ipaA, ['status' => StatusMateri::Draf]), ['Klorofil di materi draf.']);
        $belum = $this->berkas($this->materi($this->s->ipaA), ['status_indeks' => StatusIndeks::Diproses]);
        $this->bagian($belum, ['Klorofil di berkas yang belum selesai diindeks.']);
        $gagal = $this->berkas($this->materi($this->s->ipaA), ['status_indeks' => StatusIndeks::Gagal]);
        $this->bagian($gagal, ['Klorofil di berkas gagal.']);

        $this->cari($this->s->siswaA1, 'klorofil')->assertJsonCount(0, 'data');
        $this->cari($this->s->guru1, 'klorofil')->assertJsonCount(0, 'data');
    }

    #[Test]
    public function guru_mencari_hanya_di_materi_miliknya(): void
    {
        $this->terindeks($this->materi($this->s->ipaA), ['Klorofil di kelas A milik guru satu.']);
        $this->terindeks($this->materi($this->s->ipaB), ['Klorofil di kelas B milik guru dua.']);

        $this->cari($this->s->guru1, 'klorofil')->assertJsonCount(1, 'data');
        $this->assertStringContainsString('guru satu', $this->cari($this->s->guru1, 'klorofil')->json('data.0.teks'));
        $this->assertStringContainsString('guru dua', $this->cari($this->s->guru2, 'klorofil')->json('data.0.teks'));
    }

    #[Test]
    public function admin_akun_nonaktif_dan_siswa_tanpa_kelas_tidak_mendapat_hasil(): void
    {
        $this->terindeks($this->materi($this->s->ipaA), ['Klorofil menyerap cahaya.']);
        $nonaktif = $this->siswa($this->s->kelasA, ['aktif' => false]);

        $this->cari($this->s->admin, 'klorofil')->assertJsonCount(0, 'data');
        $this->cari($nonaktif, 'klorofil')->assertJsonCount(0, 'data');
        $this->cari($this->siswa(), 'klorofil')->assertJsonCount(0, 'data');
    }

    #[Test]
    public function permintaan_pencarian_yang_tidak_valid_ditolak(): void
    {
        $this->getJson(route('api.n8n.pencarian.index'), $this->token())->assertUnprocessable();
        $this->cari($this->s->siswaA1, 'ab')->assertUnprocessable();
        $this->getJson(route('api.n8n.pencarian.index', ['user_id' => 99999, 'q' => 'klorofil']), $this->token())->assertUnprocessable();
    }

    #[Test]
    public function pertanyaan_hanya_berisi_kata_umum_tidak_menghasilkan_apa_apa_dan_tidak_error(): void
    {
        $this->terindeks($this->materi($this->s->ipaA), ['Klorofil menyerap cahaya.']);

        $this->cari($this->s->siswaA1, 'apa yang dengan itu?')->assertOk()->assertJsonCount(0, 'data');
    }

    #[Test]
    public function karakter_khusus_boolean_fulltext_tidak_merusak_pencarian(): void
    {
        $this->terindeks($this->materi($this->s->ipaA), ['Klorofil menyerap cahaya matahari.']);

        $this->cari($this->s->siswaA1, '+klorofil -cahaya "matahari" ~ * (fungsi) @@')->assertOk();
        $this->cari($this->s->siswaA1, "klorofil'; DROP TABLE users; --")->assertOk();
        $this->assertDatabaseCount('users', 6);
    }

    #[Test]
    public function faq_aktif_dikirim_ke_n8n(): void
    {
        Artisan::call('db:seed', ['--class' => FaqSeeder::class, '--force' => true]);
        $nonaktif = Faq::first();
        $nonaktif->update(['aktif' => false]);

        $respons = $this->getJson(route('api.n8n.faq.index'), $this->token())->assertOk();

        $respons->assertJsonStructure(['data' => [['pertanyaan', 'jawaban']]]);
        $this->assertNotContains($nonaktif->pertanyaan, collect($respons->json('data'))->pluck('pertanyaan')->all());
        $this->assertSame(Faq::aktif()->count(), count($respons->json('data')));
    }
}
