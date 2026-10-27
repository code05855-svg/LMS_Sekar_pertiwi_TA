<?php

namespace Tests\Feature;

use App\Enums\StatusPengumpulan;
use App\Models\PengumpulanTugas;
use App\Models\Tugas;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TugasTest extends TestCase
{
    private object $s;

    protected function setUp(): void
    {
        parent::setUp();

        $this->s = $this->sekolah();
        Carbon::setTestNow('2026-10-26 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function jawaban(string $nama = 'jawaban.pdf'): UploadedFile
    {
        return str_ends_with($nama, '.pdf') ? $this->pdf($nama) : UploadedFile::fake()->create($nama, 20);
    }

    // ------------------------------------------------------------ guru: tugas

    #[Test]
    public function guru_membuat_mengubah_dan_menghapus_tugas(): void
    {
        $this->actingAs($this->s->guru1)->post(route('guru.tugas.store'), [
            'penugasan_guru_id' => $this->s->ipaA->id, 'judul' => 'Laporan', 'instruksi' => 'Amati.', 'tenggat' => '2026-10-30T23:59', 'nilai_maksimum' => 100,
        ])->assertRedirect();

        $tugas = Tugas::firstOrFail();
        $this->assertSame('2026-10-30 23:59:00', $tugas->tenggat->format('Y-m-d H:i:s'));

        $this->put(route('guru.tugas.update', $tugas), ['judul' => 'Laporan 2', 'instruksi' => '', 'tenggat' => '2026-11-02T08:00', 'nilai_maksimum' => 50])
            ->assertRedirect(route('guru.tugas.show', $tugas));
        $this->assertSame(50, $tugas->fresh()->nilai_maksimum);

        $this->delete(route('guru.tugas.destroy', $tugas))->assertRedirect();
        $this->assertModelMissing($tugas);
    }

    #[Test]
    public function validasi_tugas(): void
    {
        $this->actingAs($this->s->guru1);
        $dasar = ['penugasan_guru_id' => $this->s->ipaA->id, 'judul' => 'T', 'tenggat' => '2026-10-30T23:59', 'nilai_maksimum' => 100];

        $this->post(route('guru.tugas.store'), ['judul' => ''] + $dasar)->assertSessionHasErrors('judul');
        $this->post(route('guru.tugas.store'), ['tenggat' => 'kemarin sore'] + $dasar)->assertSessionHasErrors('tenggat');
        $this->post(route('guru.tugas.store'), ['nilai_maksimum' => 0] + $dasar)->assertSessionHasErrors('nilai_maksimum');
        $this->post(route('guru.tugas.store'), ['nilai_maksimum' => 5000] + $dasar)->assertSessionHasErrors('nilai_maksimum');
        $this->post(route('guru.tugas.store'), ['penugasan_guru_id' => $this->s->ipaB->id] + $dasar)->assertSessionHasErrors('penugasan_guru_id');
        $this->assertSame(0, Tugas::count());
    }

    #[Test]
    public function menghapus_tugas_juga_menghapus_berkas_jawaban_siswa(): void
    {
        $tugas = $this->tugas($this->s->ipaA);
        $p = $this->pengumpulan($tugas, $this->s->siswaA1);

        $this->actingAs($this->s->guru1)->delete(route('guru.tugas.destroy', $tugas))->assertRedirect();

        $this->assertModelMissing($p);
        Storage::disk('pengumpulan')->assertMissing($p->path);
    }

    #[Test]
    public function guru_lain_dan_siswa_tidak_bisa_mengelola_tugas(): void
    {
        $tugas = $this->tugas($this->s->ipaA);

        $this->actingAs($this->s->guru2);
        $this->put(route('guru.tugas.update', $tugas), ['judul' => 'X', 'tenggat' => '2026-10-30T10:00', 'nilai_maksimum' => 100])->assertForbidden();
        $this->delete(route('guru.tugas.destroy', $tugas))->assertForbidden();

        $this->actingAs($this->s->siswaA1);
        $this->post(route('guru.tugas.store'), ['penugasan_guru_id' => $this->s->ipaA->id, 'judul' => 'X', 'tenggat' => '2026-10-30T10:00', 'nilai_maksimum' => 100])->assertForbidden();
        $this->delete(route('guru.tugas.destroy', $tugas))->assertForbidden();

        $this->assertModelExists($tugas);
    }

    // ---------------------------------------------------- siswa: pengumpulan

    #[Test]
    public function pengumpulan_sebelum_tenggat_berstatus_tepat_waktu(): void
    {
        $tugas = $this->tugas($this->s->ipaA, ['tenggat' => now()->addHour()]);

        $this->actingAs($this->s->siswaA1)
            ->post(route('siswa.pengumpulan-tugas.store', $tugas), ['berkas' => $this->jawaban()])
            ->assertRedirect(route('siswa.tugas.show', $tugas))
            ->assertSessionHas('sukses');

        $p = PengumpulanTugas::firstOrFail();
        $this->assertSame(StatusPengumpulan::TepatWaktu, $p->status);
        $this->assertSame($this->s->siswaA1->id, $p->siswa_id);
        $this->assertSame('jawaban.pdf', $p->nama_asli);
        $this->assertNull($p->nilai);
        Storage::disk('pengumpulan')->assertExists($p->path);
    }

    #[Test]
    public function pengumpulan_tepat_di_detik_tenggat_masih_tepat_waktu_dan_sesudahnya_terlambat(): void
    {
        $tugas = $this->tugas($this->s->ipaA, ['tenggat' => now()]);

        $this->actingAs($this->s->siswaA1)->post(route('siswa.pengumpulan-tugas.store', $tugas), ['berkas' => $this->jawaban()]);
        $this->assertSame(StatusPengumpulan::TepatWaktu, PengumpulanTugas::where('siswa_id', $this->s->siswaA1->id)->firstOrFail()->status);

        Carbon::setTestNow(now()->addSecond());
        $this->actingAs($this->s->siswaA2)->post(route('siswa.pengumpulan-tugas.store', $tugas), ['berkas' => $this->jawaban()]);
        $this->assertSame(StatusPengumpulan::Terlambat, PengumpulanTugas::where('siswa_id', $this->s->siswaA2->id)->firstOrFail()->status);
    }

    #[Test]
    public function pengumpulan_setelah_tenggat_tetap_diterima_sebagai_terlambat(): void
    {
        $tugas = $this->tugas($this->s->ipaA, ['tenggat' => now()->subDay()]);

        $this->actingAs($this->s->siswaA1)
            ->post(route('siswa.pengumpulan-tugas.store', $tugas), ['berkas' => $this->jawaban()])
            ->assertSessionHas('sukses');

        $this->assertSame(StatusPengumpulan::Terlambat, PengumpulanTugas::firstOrFail()->status);
    }

    #[Test]
    public function siswa_tidak_bisa_mengumpulkan_dua_kali(): void
    {
        $tugas = $this->tugas($this->s->ipaA);
        $this->actingAs($this->s->siswaA1);

        $this->post(route('siswa.pengumpulan-tugas.store', $tugas), ['berkas' => $this->jawaban()]);
        $this->post(route('siswa.pengumpulan-tugas.store', $tugas), ['berkas' => $this->jawaban('lain.pdf')])->assertSessionHas('galat');

        $this->assertSame(1, PengumpulanTugas::count());
    }

    #[Test]
    public function berkas_jawaban_wajib_diisi_dengan_jenis_yang_diizinkan_dan_tidak_terlalu_besar(): void
    {
        $tugas = $this->tugas($this->s->ipaA);
        $this->actingAs($this->s->siswaA1);

        $this->post(route('siswa.pengumpulan-tugas.store', $tugas), [])->assertSessionHasErrors('berkas');
        $this->post(route('siswa.pengumpulan-tugas.store', $tugas), ['berkas' => UploadedFile::fake()->create('skrip.php', 5)])->assertSessionHasErrors('berkas');
        $this->post(route('siswa.pengumpulan-tugas.store', $tugas), ['berkas' => UploadedFile::fake()->create('skrip.exe', 5)])->assertSessionHasErrors('berkas');
        $this->post(route('siswa.pengumpulan-tugas.store', $tugas), ['berkas' => UploadedFile::fake()->create('besar.pdf', config('lms.unggah.tugas_kb') + 1)])->assertSessionHasErrors('berkas');

        $this->assertSame(0, PengumpulanTugas::count());

        foreach (['foto.jpg', 'laporan.docx', 'data.xlsx', 'slide.pptx', 'arsip.zip'] as $nama) {
            $this->post(route('siswa.pengumpulan-tugas.store', $this->tugas($this->s->ipaA)), ['berkas' => UploadedFile::fake()->create($nama, 20)])->assertSessionHasNoErrors();
        }
        $this->assertSame(5, PengumpulanTugas::count());
    }

    #[Test]
    public function siswa_kelas_lain_dan_guru_tidak_bisa_mengumpulkan(): void
    {
        $tugas = $this->tugas($this->s->ipaA);

        $this->actingAs($this->s->siswaB1)->post(route('siswa.pengumpulan-tugas.store', $tugas), ['berkas' => $this->jawaban()])->assertForbidden();
        $this->actingAs($this->s->guru1)->post(route('siswa.pengumpulan-tugas.store', $tugas), ['berkas' => $this->jawaban()])->assertForbidden();

        $this->assertSame(0, PengumpulanTugas::count());
    }

    #[Test]
    public function mengganti_berkas_memperbarui_waktu_status_dan_menghapus_file_lama(): void
    {
        $tugas = $this->tugas($this->s->ipaA, ['tenggat' => now()->addHour()]);
        $p = $this->pengumpulan($tugas, $this->s->siswaA1);
        $lama = $p->path;

        Carbon::setTestNow(now()->addHours(2));

        $this->actingAs($this->s->siswaA1)
            ->put(route('siswa.pengumpulan-tugas.update', $p), ['berkas' => $this->jawaban('revisi.pdf')])
            ->assertRedirect(route('siswa.tugas.show', $tugas));

        $p->refresh();
        $this->assertSame('revisi.pdf', $p->nama_asli);
        $this->assertSame(StatusPengumpulan::Terlambat, $p->status);
        $this->assertTrue($p->waktu_kumpul->equalTo(now()));
        Storage::disk('pengumpulan')->assertMissing($lama);
        Storage::disk('pengumpulan')->assertExists($p->path);
    }

    #[Test]
    public function siswa_menarik_jawaban_lalu_bisa_mengumpulkan_lagi(): void
    {
        $tugas = $this->tugas($this->s->ipaA);
        $p = $this->pengumpulan($tugas, $this->s->siswaA1);

        $this->actingAs($this->s->siswaA1)->delete(route('siswa.pengumpulan-tugas.destroy', $p))->assertRedirect();

        $this->assertModelMissing($p);
        Storage::disk('pengumpulan')->assertMissing($p->path);
        $this->post(route('siswa.pengumpulan-tugas.store', $tugas), ['berkas' => $this->jawaban()])->assertSessionHas('sukses');
    }

    #[Test]
    public function jawaban_yang_sudah_dinilai_tidak_bisa_diganti_atau_ditarik(): void
    {
        $tugas = $this->tugas($this->s->ipaA);
        $p = $this->pengumpulan($tugas, $this->s->siswaA1);
        $p->beriNilai(90);

        $this->actingAs($this->s->siswaA1);
        $this->put(route('siswa.pengumpulan-tugas.update', $p), ['berkas' => $this->jawaban('curang.pdf')])->assertForbidden();
        $this->delete(route('siswa.pengumpulan-tugas.destroy', $p))->assertForbidden();

        $this->assertSame('jawaban.pdf', $p->fresh()->nama_asli);
    }

    #[Test]
    public function siswa_lain_tidak_bisa_mengganti_atau_menarik_jawaban_temannya(): void
    {
        $p = $this->pengumpulan($this->tugas($this->s->ipaA), $this->s->siswaA1);

        $this->actingAs($this->s->siswaA2);
        $this->put(route('siswa.pengumpulan-tugas.update', $p), ['berkas' => $this->jawaban()])->assertForbidden();
        $this->delete(route('siswa.pengumpulan-tugas.destroy', $p))->assertForbidden();
        $this->assertModelExists($p);
    }

    #[Test]
    public function guru_mengunduh_jawaban_dan_tahu_siapa_yang_belum_mengumpulkan(): void
    {
        $tugas = $this->tugas($this->s->ipaA);
        $p = $this->pengumpulan($tugas, $this->s->siswaA1);

        $this->actingAs($this->s->guru1);
        $this->get(route('guru.pengumpulan-tugas.unduh', $p))->assertOk();
        $this->get(route('guru.pengumpulan-tugas.index', $tugas))
            ->assertOk()
            ->assertSee('Alya Putri')
            ->assertSee('Bima Saputra')
            ->assertSee('Belum dikumpulkan')
            ->assertDontSee('Citra Dewi');
    }

    #[Test]
    public function halaman_tugas_siswa_menampilkan_status_dan_nilai(): void
    {
        $tugas = $this->tugas($this->s->ipaA);
        $this->actingAs($this->s->siswaA1);

        $this->get(route('siswa.tugas.show', $tugas))->assertOk()->assertSee('Kumpulkan tugas');

        $p = $this->pengumpulan($tugas, $this->s->siswaA1);
        $this->get(route('siswa.tugas.show', $tugas))->assertOk()->assertSee('jawaban.pdf')->assertSee('Ganti berkas');

        $p->beriNilai(87.5, 'Rapi sekali');
        $this->get(route('siswa.tugas.show', $tugas))->assertOk()->assertSee('87,5')->assertSee('Rapi sekali')->assertDontSee('Ganti berkas');
    }
}
