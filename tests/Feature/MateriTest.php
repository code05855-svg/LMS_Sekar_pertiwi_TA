<?php

namespace Tests\Feature;

use App\Enums\StatusIndeks;
use App\Enums\StatusMateri;
use App\Models\BagianMateri;
use App\Models\BerkasMateri;
use App\Models\Materi;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MateriTest extends TestCase
{
    private object $s;

    protected function setUp(): void
    {
        parent::setUp();

        $this->s = $this->sekolah();
        Http::fake(['n8n.test/webhook/indeks-dokumen' => Http::response(['ok' => true])]);
    }

    private function dataMateri(array $ubah = []): array
    {
        return $ubah + [
            'penugasan_guru_id' => $this->s->ipaA->id,
            'judul' => 'Fotosintesis',
            'deskripsi' => 'Proses membuat makanan.',
            'status' => 'terbit',
            'berkas' => [$this->pdf('fotosintesis.pdf')],
        ];
    }

    #[Test]
    public function guru_membuat_materi_dengan_pdf_dan_n8n_diminta_mengindeks(): void
    {
        $this->actingAs($this->s->guru1)
            ->post(route('guru.materi.store'), $this->dataMateri())
            ->assertRedirect();

        $materi = Materi::firstOrFail();
        $berkas = $materi->berkasMateri()->firstOrFail();

        $this->assertSame(StatusMateri::Terbit, $materi->status);
        $this->assertSame('fotosintesis.pdf', $berkas->nama_asli);
        $this->assertSame('application/pdf', $berkas->mime);
        Storage::disk('materi')->assertExists($berkas->path);

        Http::assertSent(fn (Request $r) => $r->url() === 'http://n8n.test/webhook/indeks-dokumen'
            && $r->hasHeader('X-N8N-Secret', 'rahasia-uji')
            && $r['berkas_id'] === $berkas->id);
        $this->assertSame(StatusIndeks::Diproses, $berkas->fresh()->status_indeks);
    }

    #[Test]
    public function materi_tanpa_pdf_boleh_disimpan_sebagai_draf(): void
    {
        $this->actingAs($this->s->guru1)
            ->post(route('guru.materi.store'), $this->dataMateri(['berkas' => [], 'status' => 'draf']))
            ->assertRedirect();

        $this->assertSame(StatusMateri::Draf, Materi::firstOrFail()->status);
        Http::assertNothingSent();
    }

    #[Test]
    public function validasi_judul_status_dan_berkas(): void
    {
        $this->actingAs($this->s->guru1);

        $this->post(route('guru.materi.store'), $this->dataMateri(['judul' => '']))->assertSessionHasErrors('judul');
        $this->post(route('guru.materi.store'), $this->dataMateri(['status' => 'rahasia']))->assertSessionHasErrors('status');
        $this->post(route('guru.materi.store'), $this->dataMateri(['berkas' => [UploadedFile::fake()->create('virus.exe', 10)]]))->assertSessionHasErrors('berkas.0');
        $palsu = tempnam(sys_get_temp_dir(), 'palsu');
        file_put_contents($palsu, 'bukan pdf, hanya teks biasa');
        $this->post(route('guru.materi.store'), $this->dataMateri(['berkas' => [new UploadedFile($palsu, 'palsu.pdf', 'application/pdf', null, true)]]))->assertSessionHasErrors('berkas.0');
        @unlink($palsu);
        $this->post(route('guru.materi.store'), $this->dataMateri(['berkas' => [UploadedFile::fake()->create('besar.pdf', config('lms.unggah.materi_kb') + 1, 'application/pdf')]]))->assertSessionHasErrors('berkas.0');

        $this->assertSame(0, Materi::count());
    }

    #[Test]
    public function guru_tidak_bisa_membuat_materi_untuk_kelas_yang_bukan_ampuannya(): void
    {
        $this->actingAs($this->s->guru2)
            ->post(route('guru.materi.store'), $this->dataMateri())
            ->assertSessionHasErrors('penugasan_guru_id');

        $this->assertSame(0, Materi::count());
    }

    #[Test]
    public function siswa_dan_admin_tidak_bisa_membuat_materi(): void
    {
        foreach ([$this->s->siswaA1, $this->s->admin] as $pengguna) {
            $this->actingAs($pengguna)->post(route('guru.materi.store'), $this->dataMateri())->assertForbidden();
        }
        $this->assertSame(0, Materi::count());
    }

    #[Test]
    public function n8n_mati_saat_unggah_tidak_menjatuhkan_halaman_dan_berkas_ditandai_gagal(): void
    {
        $this->tiruHttp(['n8n.test/*' => Http::response('', 500)]);

        $this->actingAs($this->s->guru1)->post(route('guru.materi.store'), $this->dataMateri())->assertRedirect();

        $berkas = BerkasMateri::firstOrFail();
        $this->assertSame(StatusIndeks::Gagal, $berkas->status_indeks);
        $this->assertNotEmpty($berkas->pesan_galat);
    }

    #[Test]
    public function n8n_tidak_terjangkau_juga_tidak_menjatuhkan_halaman(): void
    {
        $this->tiruHttp(['n8n.test/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('Connection refused')]);

        $this->actingAs($this->s->guru1)->post(route('guru.materi.store'), $this->dataMateri())->assertRedirect();

        $this->assertSame(StatusIndeks::Gagal, BerkasMateri::firstOrFail()->status_indeks);
    }

    #[Test]
    public function tombol_indeks_ulang_mengirim_ulang_ke_n8n(): void
    {
        $materi = $this->materi($this->s->ipaA);
        $berkas = $this->berkas($materi, ['status_indeks' => StatusIndeks::Gagal, 'pesan_galat' => 'x']);

        $this->actingAs($this->s->guru1)->post(route('guru.berkas.reindeks', $berkas))->assertRedirect();

        Http::assertSent(fn (Request $r) => $r['berkas_id'] === $berkas->id);
        $this->assertSame(StatusIndeks::Diproses, $berkas->fresh()->status_indeks);
    }

    #[Test]
    public function mengganti_pdf_membuang_indeks_lama_dan_file_lama(): void
    {
        $materi = $this->materi($this->s->ipaA);
        $berkas = $this->berkas($materi, ['status_indeks' => StatusIndeks::Terindeks]);
        BagianMateri::create(['berkas_materi_id' => $berkas->id, 'nomor_bagian' => 0, 'halaman' => 1, 'teks' => 'klorofil menyerap cahaya']);
        $pathLama = $berkas->path;

        $this->actingAs($this->s->guru1)
            ->put(route('guru.berkas.update', $berkas), ['berkas' => $this->pdf('versi2.pdf')])
            ->assertRedirect();

        $berkas->refresh();
        $this->assertNotSame($pathLama, $berkas->path);
        $this->assertSame('versi2.pdf', $berkas->nama_asli);
        Storage::disk('materi')->assertMissing($pathLama);
        Storage::disk('materi')->assertExists($berkas->path);
        $this->assertSame(0, $berkas->bagianMateri()->count());
        Http::assertSent(fn (Request $r) => $r['berkas_id'] === $berkas->id);
    }

    #[Test]
    public function menghapus_berkas_dan_materi_ikut_menghapus_file_dan_indeks(): void
    {
        $materi = $this->materi($this->s->ipaA);
        $berkas = $this->berkas($materi);
        BagianMateri::create(['berkas_materi_id' => $berkas->id, 'nomor_bagian' => 0, 'halaman' => 1, 'teks' => 'teks']);
        $path = $berkas->path;

        $this->actingAs($this->s->guru1)->delete(route('guru.berkas.destroy', $berkas))->assertRedirect();
        $this->assertModelMissing($berkas);
        $this->assertSame(0, BagianMateri::count());
        Storage::disk('materi')->assertMissing($path);

        $kedua = $this->berkas($materi);
        $this->delete(route('guru.materi.destroy', $materi))->assertRedirect();
        $this->assertModelMissing($materi);
        $this->assertModelMissing($kedua);
        Storage::disk('materi')->assertMissing($kedua->path);
    }

    #[Test]
    public function mengubah_status_materi_dan_menambah_pdf_dari_halaman_ubah(): void
    {
        $materi = $this->materi($this->s->ipaA, ['status' => StatusMateri::Draf]);

        $this->actingAs($this->s->guru1)->put(route('guru.materi.update', $materi), [
            'judul' => 'Judul baru', 'deskripsi' => null, 'status' => 'terbit', 'berkas' => [$this->pdf('tambahan.pdf')],
        ])->assertRedirect(route('guru.materi.show', $materi));

        $materi->refresh();
        $this->assertSame('Judul baru', $materi->judul);
        $this->assertSame(StatusMateri::Terbit, $materi->status);
        $this->assertSame(1, $materi->berkasMateri()->count());
    }

    #[Test]
    public function materi_tidak_bisa_dipindahkan_ke_kelas_lain_lewat_update(): void
    {
        $materi = $this->materi($this->s->ipaA);

        $this->actingAs($this->s->guru1)->put(route('guru.materi.update', $materi), [
            'judul' => 'X', 'status' => 'terbit', 'penugasan_guru_id' => $this->s->mtkA->id,
        ])->assertRedirect();

        $this->assertSame($this->s->ipaA->id, $materi->fresh()->penugasan_guru_id);
    }

    #[Test]
    public function guru_lain_tidak_bisa_mengubah_menghapus_atau_mengganti_pdf(): void
    {
        $materi = $this->materi($this->s->ipaA);
        $berkas = $this->berkas($materi);
        $this->actingAs($this->s->guru2);

        $this->put(route('guru.materi.update', $materi), ['judul' => 'Dibajak', 'status' => 'terbit'])->assertForbidden();
        $this->delete(route('guru.materi.destroy', $materi))->assertForbidden();
        $this->put(route('guru.berkas.update', $berkas), ['berkas' => $this->pdf()])->assertForbidden();
        $this->delete(route('guru.berkas.destroy', $berkas))->assertForbidden();
        $this->post(route('guru.berkas.reindeks', $berkas))->assertForbidden();
        $this->post(route('guru.berkas.store', $materi), ['berkas' => [$this->pdf()]])->assertForbidden();

        $this->assertSame('Fotosintesis', $materi->fresh()->judul);
        $this->assertModelExists($berkas);
    }

    // --------------------------------------------------------- sisi siswa

    #[Test]
    public function siswa_hanya_melihat_materi_terbit_di_kelasnya(): void
    {
        $terbit = $this->materi($this->s->ipaA, ['judul' => 'Materi Terbit']);
        $this->materi($this->s->ipaA, ['judul' => 'Materi Rahasia Draf', 'status' => StatusMateri::Draf]);
        $this->materi($this->s->ipaB, ['judul' => 'Materi Kelas B']);

        $this->actingAs($this->s->siswaA1)
            ->get(route('siswa.mata-pelajaran.show', $this->s->ipaA))
            ->assertOk()
            ->assertSee('Materi Terbit')
            ->assertDontSee('Materi Rahasia Draf')
            ->assertDontSee('Materi Kelas B');

        $this->get(route('siswa.materi.show', $terbit))->assertOk()->assertSee('Materi Terbit');
    }

    #[Test]
    public function siswa_mengunduh_dan_membuka_pdf_materi_terbit(): void
    {
        $materi = $this->materi($this->s->ipaA);
        $berkas = $this->berkas($materi);
        $this->actingAs($this->s->siswaA1);

        $unduh = $this->get(route('siswa.materi.unduh', $berkas));
        $unduh->assertOk();
        $this->assertStringContainsString('attachment', $unduh->headers->get('content-disposition'));

        $lihat = $this->get(route('siswa.materi.unduh', [$berkas, 'lihat' => 1]));
        $lihat->assertOk();
        $this->assertStringContainsString('inline', $lihat->headers->get('content-disposition'));
        $this->assertSame('application/pdf', $lihat->headers->get('content-type'));
    }

    #[Test]
    public function siswa_tidak_bisa_mengunduh_pdf_materi_draf_atau_kelas_lain(): void
    {
        $draf = $this->materi($this->s->ipaA, ['status' => StatusMateri::Draf]);
        $berkasDraf = $this->berkas($draf);
        $berkasB = $this->berkas($this->materi($this->s->ipaB));

        $this->actingAs($this->s->siswaA1);
        $this->get(route('siswa.materi.unduh', $berkasDraf))->assertForbidden();
        $this->get(route('siswa.materi.unduh', $berkasB))->assertForbidden();
    }

    #[Test]
    public function pdf_yang_hilang_dari_penyimpanan_menampilkan_404_bukan_500(): void
    {
        $berkas = $this->berkas($this->materi($this->s->ipaA));
        Storage::disk('materi')->delete($berkas->path);

        $this->actingAs($this->s->siswaA1)->get(route('siswa.materi.unduh', $berkas))->assertNotFound();
        $this->actingAs($this->s->guru1)->get(route('guru.berkas.show', $berkas))->assertNotFound();
    }

    #[Test]
    public function guru_membuka_dan_mengunduh_pdf_miliknya(): void
    {
        $berkas = $this->berkas($this->materi($this->s->ipaA));
        $this->actingAs($this->s->guru1);

        $this->get(route('guru.berkas.show', $berkas))->assertOk();
        $this->assertStringContainsString('attachment', $this->get(route('guru.berkas.show', [$berkas, 'unduh' => 1]))->headers->get('content-disposition'));
    }
}
