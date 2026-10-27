<?php

namespace Tests\Feature;

use App\Enums\StatusChat;
use App\Models\RiwayatChat;
use App\Services\ChatbotService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChatbotTest extends TestCase
{
    private object $s;

    protected function setUp(): void
    {
        parent::setUp();

        $this->s = $this->sekolah();
    }

    private function n8nMenjawab(array $balasan = []): void
    {
        $this->tiruHttp(['n8n.test/webhook/tanya-jawab' => Http::response($balasan + [
            'status' => 'berhasil',
            'jawaban' => 'Klorofil adalah pigmen hijau penyerap cahaya [1].',
            'sumber' => [['no' => 1, 'materi_id' => 7, 'materi' => 'Fotosintesis', 'mapel' => 'IPA', 'berkas_id' => 3, 'berkas' => 'foto.pdf', 'halaman' => 2]],
            'token_masuk' => 321,
            'token_keluar' => 45,
        ])]);
    }

    private function tanya(string $pertanyaan = 'Apa fungsi klorofil?'): \Illuminate\Testing\TestResponse
    {
        return $this->post(route('chatbot.kirim'), ['pertanyaan' => $pertanyaan], ['Accept' => 'application/json, text/html;q=0.9']);
    }

    #[Test]
    public function pertanyaan_dikirim_ke_n8n_dengan_rahasia_dan_hanya_membawa_pertanyaan_serta_user_id(): void
    {
        $this->n8nMenjawab();

        $this->actingAs($this->s->siswaA1);
        $this->tanya('Apa fungsi klorofil?')->assertOk()->assertSee('Klorofil adalah pigmen hijau');

        Http::assertSentCount(1);
        Http::assertSent(function (Request $r) {
            return $r->url() === 'http://n8n.test/webhook/tanya-jawab'
                && $r->hasHeader('X-N8N-Secret', 'rahasia-uji')
                && $r->data() === ['pertanyaan' => 'Apa fungsi klorofil?', 'user_id' => $this->s->siswaA1->id];
        });
    }

    #[Test]
    public function jawaban_sumber_token_dan_waktu_tersimpan_di_riwayat(): void
    {
        $this->n8nMenjawab();

        $this->actingAs($this->s->siswaA1);
        $this->tanya()->assertOk();

        $r = RiwayatChat::firstOrFail();
        $this->assertSame($this->s->siswaA1->id, $r->user_id);
        $this->assertSame(StatusChat::Berhasil, $r->status);
        $this->assertSame('Apa fungsi klorofil?', $r->pertanyaan);
        $this->assertSame(321, $r->token_masuk);
        $this->assertSame(45, $r->token_keluar);
        $this->assertNotNull($r->waktu_respons_ms);
        $this->assertSame('Fotosintesis', $r->sumber[0]['materi']);
        $this->assertSame(1, $r->sumber[0]['no']);
        $this->assertSame(2, $r->sumber[0]['halaman']);
    }

    #[Test]
    public function jawaban_menampilkan_sumber_dengan_tautan_ke_materi(): void
    {
        $this->n8nMenjawab();

        $this->actingAs($this->s->siswaA1);
        $this->tanya()->assertSee('Sumber')->assertSee('Fotosintesis')->assertSee('halaman 2')->assertSee(route('siswa.materi.show', 7));
    }

    #[Test]
    public function field_asing_dari_n8n_dibuang_dan_teks_jawaban_tidak_dieksekusi_sebagai_html(): void
    {
        $this->n8nMenjawab([
            'jawaban' => '<script>alert(1)</script> Jawaban aman',
            'sumber' => [['materi' => 'M', 'materi_id' => 1, 'rahasia' => 'bocor', 'halaman' => 'x'], 'bukan-array'],
        ]);

        $this->actingAs($this->s->siswaA1);
        $this->tanya()->assertDontSee('<script>alert(1)</script>', false)->assertSee('Jawaban aman');

        $sumber = RiwayatChat::firstOrFail()->sumber;
        $this->assertCount(1, $sumber);
        $this->assertArrayNotHasKey('rahasia', $sumber[0]);
        $this->assertNull($sumber[0]['halaman']);
    }

    #[Test]
    public function jawaban_tidak_ditemukan_dari_n8n_dicatat_gagal_tanpa_sumber(): void
    {
        $this->n8nMenjawab(['status' => 'gagal', 'jawaban' => 'Maaf, saya tidak menemukan jawabannya di materi.', 'sumber' => [['materi' => 'Salah']]]);

        $this->actingAs($this->s->siswaA1);
        $this->tanya()->assertOk()->assertSee('tidak menemukan jawabannya');

        $r = RiwayatChat::firstOrFail();
        $this->assertSame(StatusChat::Gagal, $r->status);
        $this->assertSame([], $r->sumber);
    }

    #[Test]
    public function n8n_error_timeout_atau_balasan_rusak_tidak_menjatuhkan_halaman_dan_riwayat_tetap_tersimpan(): void
    {
        $kasus = [
            'http 500' => Http::response('boom', 500),
            'koneksi putus' => fn () => throw new ConnectionException('cURL error 7'),
            'bukan json' => Http::response('<html>nginx</html>', 200),
            'json kosong' => Http::response([], 200),
        ];

        foreach ($kasus as $nama => $tiruan) {
            $this->tiruHttp(['n8n.test/*' => $tiruan]);
            RiwayatChat::query()->delete();

            $this->actingAs($this->s->siswaA1);
            $this->tanya("Pertanyaan kasus {$nama}")->assertOk()->assertSee(ChatbotService::PESAN_GAGAL);

            $r = RiwayatChat::firstOrFail();
            $this->assertSame(StatusChat::Gagal, $r->status, $nama);
            $this->assertSame("Pertanyaan kasus {$nama}", $r->pertanyaan, $nama);
        }
    }

    #[Test]
    public function url_webhook_kosong_ditangani_tanpa_error(): void
    {
        config(['services.n8n.webhook_tanya' => '']);
        Http::fake();

        $this->actingAs($this->s->siswaA1);
        $this->tanya()->assertOk()->assertSee(ChatbotService::PESAN_GAGAL);

        Http::assertNothingSent();
    }

    #[Test]
    public function pertanyaan_divalidasi_dan_spasi_dipangkas(): void
    {
        $this->n8nMenjawab();
        $this->actingAs($this->s->siswaA1);

        $this->post(route('chatbot.kirim'), ['pertanyaan' => ''], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->post(route('chatbot.kirim'), ['pertanyaan' => 'ab'], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->post(route('chatbot.kirim'), ['pertanyaan' => '   a   '], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->post(route('chatbot.kirim'), ['pertanyaan' => str_repeat('a', 501)], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->post(route('chatbot.kirim'), ['pertanyaan' => ['array']], ['Accept' => 'application/json'])->assertUnprocessable();
        Http::assertNothingSent();

        $this->tanya("   Apa itu sel?   ")->assertOk();
        $this->assertSame('Apa itu sel?', RiwayatChat::firstOrFail()->pertanyaan);
    }

    #[Test]
    public function satu_percakapan_memakai_satu_sesi_sampai_percakapan_baru(): void
    {
        $this->n8nMenjawab();
        $this->actingAs($this->s->siswaA1);

        $this->tanya('Pertanyaan pertama');
        $this->tanya('Pertanyaan kedua');
        $sesi = RiwayatChat::pluck('sesi_id')->unique();
        $this->assertCount(1, $sesi);

        $this->get(route('chatbot.index'))->assertOk()->assertSee('Pertanyaan pertama')->assertSee('Pertanyaan kedua');

        $this->post(route('chatbot.baru'))->assertRedirect(route('chatbot.index'));
        $this->tanya('Pertanyaan ketiga');
        $this->assertCount(2, RiwayatChat::pluck('sesi_id')->unique());

        $this->get(route('chatbot.index'))->assertSee('Pertanyaan ketiga')->assertDontSee('Pertanyaan pertama');
    }

    #[Test]
    public function riwayat_hanya_menampilkan_pertanyaan_milik_sendiri(): void
    {
        $this->n8nMenjawab();
        $this->actingAs($this->s->siswaA1);
        $this->tanya('Pertanyaan Alya');
        $this->actingAs($this->s->siswaA2);
        $this->tanya('Pertanyaan Bima');

        $this->get(route('chatbot.riwayat'))->assertOk()->assertSee('Pertanyaan Bima')->assertDontSee('Pertanyaan Alya');
        $this->get(route('chatbot.index', ['fragmen' => 1]))->assertDontSee('Pertanyaan Alya');
    }

    #[Test]
    public function guru_juga_boleh_bertanya_tetapi_admin_dan_tamu_tidak(): void
    {
        $this->n8nMenjawab();

        $this->actingAs($this->s->guru1);
        $this->tanya()->assertOk();

        $this->actingAs($this->s->admin);
        $this->tanya()->assertForbidden();

        $this->app['auth']->forgetGuards();
        $this->tanya()->assertUnauthorized();
        $this->assertSame(1, RiwayatChat::count());
    }

    #[Test]
    public function batas_sepuluh_pertanyaan_per_menit_per_pengguna(): void
    {
        $this->n8nMenjawab();

        $this->actingAs($this->s->siswaA1);
        for ($i = 1; $i <= 10; $i++) {
            $this->tanya("Pertanyaan nomor {$i}")->assertOk();
        }
        $this->tanya('Pertanyaan kesebelas')->assertStatus(429);

        $this->actingAs($this->s->siswaA2);
        $this->tanya('Pertanyaan Bima tidak terpengaruh')->assertOk();
    }

    #[Test]
    public function halaman_chatbot_menampilkan_saran_dari_faq(): void
    {
        \App\Models\Faq::create(['pertanyaan' => 'Bagaimana cara mengumpulkan tugas?', 'jawaban' => 'Buka tugas lalu unggah berkas.', 'aktif' => true]);

        $this->actingAs($this->s->siswaA1)->get(route('chatbot.index'))->assertOk()->assertSee('Bagaimana cara mengumpulkan tugas?');
    }
}
