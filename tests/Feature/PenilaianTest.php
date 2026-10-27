<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PenilaianTest extends TestCase
{
    private object $s;

    protected function setUp(): void
    {
        parent::setUp();

        $this->s = $this->sekolah();
    }

    #[Test]
    public function guru_menilai_dengan_komentar_dan_waktu_penilaian_tercatat(): void
    {
        $tugas = $this->tugas($this->s->ipaA);
        $p = $this->pengumpulan($tugas, $this->s->siswaA1);

        $this->actingAs($this->s->guru1)
            ->put(route('guru.penilaian.update', $p), ['nilai' => '87.5', 'komentar' => 'Rapi sekali'])
            ->assertRedirect(route('guru.pengumpulan-tugas.show', $p));

        $p->refresh();
        $this->assertSame('87.50', $p->nilai);
        $this->assertSame('Rapi sekali', $p->komentar);
        $this->assertNotNull($p->dinilai_pada);
        $this->assertTrue($p->telahDinilai());
    }

    #[Test]
    public function nilai_nol_sah_dan_dianggap_sudah_dinilai(): void
    {
        $p = $this->pengumpulan($this->tugas($this->s->ipaA), $this->s->siswaA1);

        $this->actingAs($this->s->guru1)->put(route('guru.penilaian.update', $p), ['nilai' => 0])->assertSessionHasNoErrors();

        $this->assertTrue($p->fresh()->telahDinilai());
        $this->assertSame('0.00', $p->fresh()->nilai);
    }

    #[Test]
    public function nilai_harus_angka_dalam_rentang_nol_sampai_maksimum_tugas(): void
    {
        $tugas = $this->tugas($this->s->ipaA, ['nilai_maksimum' => 50]);
        $p = $this->pengumpulan($tugas, $this->s->siswaA1);
        $this->actingAs($this->s->guru1);

        foreach (['', 'abc', '-1', '50.01', '100'] as $salah) {
            $this->put(route('guru.penilaian.update', $p), ['nilai' => $salah])->assertSessionHasErrors('nilai');
        }
        $this->put(route('guru.penilaian.update', $p), ['nilai' => '50'])->assertSessionHasNoErrors();
        $this->assertSame('50.00', $p->fresh()->nilai);
    }

    #[Test]
    public function nilai_bisa_diubah_kemudian(): void
    {
        $p = $this->pengumpulan($this->tugas($this->s->ipaA), $this->s->siswaA1);
        $this->actingAs($this->s->guru1);

        $this->put(route('guru.penilaian.update', $p), ['nilai' => 70]);
        $this->put(route('guru.penilaian.update', $p), ['nilai' => 95, 'komentar' => 'Revisi bagus']);

        $this->assertSame('95.00', $p->fresh()->nilai);
    }

    #[Test]
    public function simpan_dan_buka_berikutnya_melompat_ke_jawaban_belum_dinilai_tertua(): void
    {
        $tugas = $this->tugas($this->s->ipaA);
        $pertama = $this->pengumpulan($tugas, $this->s->siswaA1, ['waktu_kumpul' => now()->subHours(3)]);
        $kedua = $this->pengumpulan($tugas, $this->s->siswaA2, ['waktu_kumpul' => now()->subHours(1)]);
        $this->actingAs($this->s->guru1);

        $this->put(route('guru.penilaian.update', $pertama), ['nilai' => 80, 'lanjut' => 'berikutnya'])
            ->assertRedirect(route('guru.pengumpulan-tugas.show', $kedua));

        $this->put(route('guru.penilaian.update', $kedua), ['nilai' => 75, 'lanjut' => 'berikutnya'])
            ->assertRedirect(route('guru.pengumpulan-tugas.index', $tugas))
            ->assertSessionHas('sukses');
    }

    #[Test]
    public function siswa_admin_dan_guru_lain_tidak_bisa_menilai(): void
    {
        $p = $this->pengumpulan($this->tugas($this->s->ipaA), $this->s->siswaA1);

        foreach ([$this->s->siswaA1, $this->s->siswaA2, $this->s->admin, $this->s->guru2] as $pengguna) {
            $this->actingAs($pengguna)->put(route('guru.penilaian.update', $p), ['nilai' => 100])->assertForbidden();
        }

        $this->assertFalse($p->fresh()->telahDinilai());
    }

    #[Test]
    public function siswa_melihat_nilai_di_halaman_nilai_dengan_rata_rata_skala_seratus(): void
    {
        $a = $this->tugas($this->s->ipaA, ['judul' => 'Tugas A', 'nilai_maksimum' => 100]);
        $b = $this->tugas($this->s->mtkA, ['judul' => 'Tugas B', 'nilai_maksimum' => 50]);
        $c = $this->tugas($this->s->ipaA, ['judul' => 'Tugas C belum dikumpulkan']);
        $this->pengumpulan($a, $this->s->siswaA1)->beriNilai(80);
        $this->pengumpulan($b, $this->s->siswaA1)->beriNilai(50); // 100%

        $this->actingAs($this->s->siswaA1)
            ->get(route('siswa.nilai.index'))
            ->assertOk()
            ->assertSee('Tugas A')
            ->assertSee('Tugas B')
            ->assertSee('Tugas C belum dikumpulkan')
            ->assertSee('90,0'); // (80 + 100) / 2
    }

    #[Test]
    public function siswa_tidak_melihat_nilai_temannya(): void
    {
        $tugas = $this->tugas($this->s->ipaA, ['judul' => 'Tugas Bersama']);
        $this->pengumpulan($tugas, $this->s->siswaA2)->beriNilai(99, 'Komentar rahasia Bima');

        $this->actingAs($this->s->siswaA1)
            ->get(route('siswa.tugas.show', $tugas))
            ->assertOk()
            ->assertDontSee('Komentar rahasia Bima');
        $this->get(route('siswa.nilai.index'))->assertDontSee('99');
    }
}
