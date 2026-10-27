<?php

namespace Tests\Feature;

use App\Models\ForumBalasan;
use App\Models\ForumTopik;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ForumTest extends TestCase
{
    private object $s;

    protected function setUp(): void
    {
        parent::setUp();

        $this->s = $this->sekolah();
    }

    private function topik(?\App\Models\User $pembuat = null, string $judul = 'Apa itu klorofil?'): ForumTopik
    {
        return ForumTopik::create([
            'penugasan_guru_id' => $this->s->ipaA->id,
            'user_id' => ($pembuat ?? $this->s->siswaA1)->id,
            'judul' => $judul,
            'isi' => 'Tolong jelaskan fungsinya.',
        ]);
    }

    #[Test]
    public function siswa_dan_guru_membuat_topik_di_kelas_mereka(): void
    {
        foreach ([$this->s->siswaA1, $this->s->guru1] as $pengguna) {
            $this->actingAs($pengguna)
                ->post(route('forum.topik.store', $this->s->ipaA), ['judul' => 'Topik dari '.$pengguna->nama, 'isi' => 'Isi diskusi.'])
                ->assertRedirect();
        }

        $this->assertSame(2, ForumTopik::count());
        $this->assertSame($this->s->ipaA->id, ForumTopik::first()->penugasan_guru_id);
    }

    #[Test]
    public function validasi_topik_dan_balasan(): void
    {
        $topik = $this->topik();
        $this->actingAs($this->s->siswaA1);

        $this->post(route('forum.topik.store', $this->s->ipaA), ['judul' => '', 'isi' => 'x'])->assertSessionHasErrors('judul');
        $this->post(route('forum.topik.store', $this->s->ipaA), ['judul' => 'x', 'isi' => ''])->assertSessionHasErrors('isi');
        $this->post(route('forum.balasan.store', $topik), ['isi' => ''])->assertSessionHasErrors('isi');
        $this->post(route('forum.balasan.store', $topik), ['isi' => str_repeat('a', 5001)])->assertSessionHasErrors('isi');
    }

    #[Test]
    public function siswa_kelas_lain_dan_guru_lain_tidak_bisa_membuat_atau_membalas(): void
    {
        $topik = $this->topik();

        foreach ([$this->s->siswaB1, $this->s->guru2, $this->s->admin] as $pengguna) {
            $this->actingAs($pengguna);
            $this->post(route('forum.topik.store', $this->s->ipaA), ['judul' => 'X', 'isi' => 'Y'])->assertForbidden();
            $this->post(route('forum.balasan.store', $topik), ['isi' => 'Y'])->assertForbidden();
        }

        $this->assertSame(1, ForumTopik::count());
        $this->assertSame(0, ForumBalasan::count());
    }

    #[Test]
    public function membalas_mengarahkan_ke_balasan_pada_halaman_yang_tepat(): void
    {
        $topik = $this->topik();
        for ($i = 1; $i <= 15; $i++) {
            ForumBalasan::create(['forum_topik_id' => $topik->id, 'user_id' => $this->s->guru1->id, 'isi' => "Balasan {$i}"]);
        }

        $respons = $this->actingAs($this->s->siswaA1)->post(route('forum.balasan.store', $topik), ['isi' => 'Balasan ke-16']);

        $balasan = ForumBalasan::where('isi', 'Balasan ke-16')->firstOrFail();
        $respons->assertRedirect(route('forum.topik.show', ['topik' => $topik, 'page' => 2]).'#balasan-'.$balasan->id);

        $this->get(route('forum.topik.show', ['topik' => $topik, 'page' => 2]))->assertOk()->assertSee('Balasan ke-16');
    }

    #[Test]
    public function pemilik_mengubah_topik_dan_balasannya_sendiri_tetapi_tidak_milik_orang_lain(): void
    {
        $topik = $this->topik($this->s->siswaA1);
        $balasanA2 = ForumBalasan::create(['forum_topik_id' => $topik->id, 'user_id' => $this->s->siswaA2->id, 'isi' => 'Balasan Bima']);

        $this->actingAs($this->s->siswaA1);
        $this->put(route('forum.topik.update', $topik), ['judul' => 'Judul diperbaiki', 'isi' => 'Isi baru'])->assertRedirect();
        $this->assertSame('Judul diperbaiki', $topik->fresh()->judul);
        $this->put(route('forum.balasan.update', $balasanA2), ['isi' => 'Dibajak'])->assertForbidden();
        $this->delete(route('forum.balasan.destroy', $balasanA2))->assertForbidden();

        $this->actingAs($this->s->siswaA2);
        $this->put(route('forum.topik.update', $topik), ['judul' => 'Dibajak', 'isi' => 'x'])->assertForbidden();
        $this->delete(route('forum.topik.destroy', $topik))->assertForbidden();
        $this->put(route('forum.balasan.update', $balasanA2), ['isi' => 'Balasan Bima (edit)'])->assertRedirect();

        $this->assertSame('Balasan Bima (edit)', $balasanA2->fresh()->isi);
        $this->assertModelExists($topik);
    }

    #[Test]
    public function guru_pengampu_menyematkan_mengunci_dan_menghapus_topik_siswa(): void
    {
        $topik = $this->topik();
        $this->actingAs($this->s->guru1);

        $this->patch(route('guru.forum.sematkan', $topik))->assertRedirect();
        $this->assertTrue($topik->fresh()->disematkan);
        $this->patch(route('guru.forum.sematkan', $topik));
        $this->assertFalse($topik->fresh()->disematkan);

        $this->patch(route('guru.forum.kunci', $topik))->assertRedirect();
        $this->assertTrue($topik->fresh()->dikunci);

        $this->delete(route('forum.topik.destroy', $topik))->assertRedirect(route('forum.topik.index', $this->s->ipaA));
        $this->assertModelMissing($topik);
    }

    #[Test]
    public function siswa_dan_guru_lain_tidak_bisa_memoderasi(): void
    {
        $topik = $this->topik();

        foreach ([$this->s->siswaA1, $this->s->siswaA2, $this->s->guru2] as $pengguna) {
            $this->actingAs($pengguna);
            $this->patch(route('guru.forum.sematkan', $topik))->assertForbidden();
            $this->patch(route('guru.forum.kunci', $topik))->assertForbidden();
        }

        $this->assertFalse($topik->fresh()->disematkan);
        $this->assertFalse($topik->fresh()->dikunci);
    }

    #[Test]
    public function topik_terkunci_menolak_balasan_dan_ubah_balasan_tetapi_tetap_terbaca(): void
    {
        $topik = $this->topik();
        $balasan = ForumBalasan::create(['forum_topik_id' => $topik->id, 'user_id' => $this->s->siswaA1->id, 'isi' => 'Balasan lama']);
        $topik->kunci();

        $this->actingAs($this->s->siswaA1);
        $this->post(route('forum.balasan.store', $topik), ['isi' => 'Balasan baru'])->assertForbidden();
        $this->put(route('forum.balasan.update', $balasan), ['isi' => 'Ubah'])->assertForbidden();
        $this->get(route('forum.topik.show', $topik))->assertOk()->assertSee('Balasan lama')->assertSee('dikunci');
        $this->assertSame(1, ForumBalasan::count());
    }

    #[Test]
    public function guru_menghapus_balasan_tidak_pantas_dan_menghapus_topik_ikut_menghapus_balasan(): void
    {
        $topik = $this->topik();
        $balasan = ForumBalasan::create(['forum_topik_id' => $topik->id, 'user_id' => $this->s->siswaA2->id, 'isi' => 'Kata kasar']);

        $this->actingAs($this->s->guru1)->delete(route('forum.balasan.destroy', $balasan))->assertRedirect();
        $this->assertModelMissing($balasan);

        $lagi = ForumBalasan::create(['forum_topik_id' => $topik->id, 'user_id' => $this->s->siswaA2->id, 'isi' => 'Balasan lain']);
        $this->delete(route('forum.topik.destroy', $topik));
        $this->assertModelMissing($lagi);
    }

    #[Test]
    public function daftar_topik_menempatkan_yang_disematkan_di_atas_dan_bisa_dicari(): void
    {
        $biasa = $this->topik(null, 'Topik biasa');
        $penting = $this->topik($this->s->guru1, 'Pengumuman penting');
        $penting->sematkan();
        $this->topik(null, 'Soal klorofil');

        $this->actingAs($this->s->siswaA1)
            ->get(route('forum.topik.index', $this->s->ipaA))
            ->assertOk()
            ->assertSeeInOrder(['Pengumuman penting', 'Topik biasa']);

        $this->get(route('forum.topik.index', [$this->s->ipaA, 'cari' => 'klorofil']))
            ->assertSee('Soal klorofil')
            ->assertDontSee('Topik biasa');
    }

    #[Test]
    public function beranda_forum_hanya_menampilkan_forum_milik_pengguna(): void
    {
        $this->topik(null, 'Topik IPA kelas A');

        $this->actingAs($this->s->siswaA1)->get(route('forum.index'))->assertOk()->assertSee('IPA')->assertSee('Matematika')->assertDontSee('Pak Dodi');
        $this->actingAs($this->s->guru2)->get(route('forum.index'))->assertOk()->assertSee('VII B')->assertDontSee('VII A');
    }
}
