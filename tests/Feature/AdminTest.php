<?php

namespace Tests\Feature;

use App\Enums\PeranPengguna;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\PenugasanGuru;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->admin();
        $this->actingAs($this->admin);
    }

    // ---------------------------------------------------------------- akun

    #[Test]
    public function admin_membuat_akun_guru_dengan_peran_dari_rute(): void
    {
        $this->post(route('admin.guru.store'), [
            'nomor_induk' => '198501012010', 'nama' => 'Bu Rina', 'email' => 'rina@sekolah.test', 'password' => 'rahasia123', 'aktif' => 1,
        ])->assertRedirect(route('admin.guru.index'));

        $guru = User::where('nomor_induk', '198501012010')->firstOrFail();
        $this->assertSame(PeranPengguna::Guru, $guru->peran);
        $this->assertNull($guru->kelas_id);
        $this->assertTrue(Hash::check('rahasia123', $guru->password));
        $this->assertTrue($guru->aktif);
    }

    #[Test]
    public function peran_tidak_bisa_disusupkan_lewat_isian_formulir(): void
    {
        $this->post(route('admin.guru.store'), [
            'nomor_induk' => 'G1', 'nama' => 'Guru', 'password' => 'rahasia123', 'peran' => 'admin',
        ])->assertRedirect();

        $this->assertSame(PeranPengguna::Guru, User::where('nomor_induk', 'G1')->firstOrFail()->peran);
    }

    #[Test]
    public function admin_membuat_siswa_langsung_di_kelas(): void
    {
        $kelas = $this->kelas();

        $this->post(route('admin.siswa.store'), [
            'nomor_induk' => '2026001', 'nama' => 'Alya Putri', 'password' => 'rahasia123', 'kelas_id' => $kelas->id,
        ])->assertRedirect(route('admin.siswa.index'));

        $siswa = User::where('nomor_induk', '2026001')->firstOrFail();
        $this->assertSame(PeranPengguna::Siswa, $siswa->peran);
        $this->assertSame($kelas->id, $siswa->kelas_id);
    }

    #[Test]
    public function guru_tidak_boleh_punya_kelas_dan_nomor_induk_harus_unik_dan_aman(): void
    {
        $kelas = $this->kelas();
        $this->guru(['nomor_induk' => 'SUDAH-ADA']);

        $this->post(route('admin.guru.store'), ['nomor_induk' => 'SUDAH-ADA', 'nama' => 'X', 'password' => 'rahasia123'])
            ->assertSessionHasErrors('nomor_induk');
        $this->post(route('admin.guru.store'), ['nomor_induk' => 'ada spasi', 'nama' => 'X', 'password' => 'rahasia123'])
            ->assertSessionHasErrors('nomor_induk');
        $this->post(route('admin.guru.store'), ['nomor_induk' => 'G2', 'nama' => 'X', 'password' => 'rahasia123', 'kelas_id' => $kelas->id])
            ->assertSessionHasErrors('kelas_id');
        $this->post(route('admin.guru.store'), ['nomor_induk' => 'G3', 'nama' => 'X', 'password' => 'pendek'])
            ->assertSessionHasErrors('password');
    }

    #[Test]
    public function mengubah_akun_tanpa_mengisi_password_tidak_mengganti_password(): void
    {
        $guru = $this->guru(['password' => 'lama12345']);
        $hash = $guru->password;

        $this->put(route('admin.guru.update', $guru), ['nomor_induk' => $guru->nomor_induk, 'nama' => 'Nama Baru', 'password' => ''])
            ->assertRedirect();

        $guru->refresh();
        $this->assertSame('Nama Baru', $guru->nama);
        $this->assertSame($hash, $guru->password);
    }

    #[Test]
    public function reset_password_dan_nonaktifkan_akun(): void
    {
        $siswa = $this->siswa();

        $this->patch(route('admin.siswa.password', $siswa), ['password' => 'sandibaru99'])->assertRedirect();
        $this->assertTrue(Hash::check('sandibaru99', $siswa->fresh()->password));

        $this->patch(route('admin.siswa.status', $siswa))->assertRedirect();
        $this->assertFalse($siswa->fresh()->aktif);

        $this->patch(route('admin.siswa.status', $siswa))->assertRedirect();
        $this->assertTrue($siswa->fresh()->aktif);
    }

    #[Test]
    public function akun_dengan_peran_lain_tidak_bisa_diubah_lewat_rute_yang_salah(): void
    {
        $siswa = $this->siswa();

        $this->get(route('admin.guru.edit', $siswa))->assertNotFound();
        $this->delete(route('admin.guru.destroy', $siswa))->assertNotFound();
        $this->assertModelExists($siswa);
    }

    #[Test]
    public function akun_tanpa_data_terhapus_dan_yang_punya_data_hanya_bisa_dinonaktifkan(): void
    {
        $s = $this->sekolah();
        $kosong = $this->guru();

        $this->delete(route('admin.guru.destroy', $kosong))->assertRedirect(route('admin.guru.index'));
        $this->assertModelMissing($kosong);

        $this->delete(route('admin.guru.destroy', $s->guru1))
            ->assertRedirect()
            ->assertSessionHas('galat');
        $this->assertModelExists($s->guru1);
    }

    #[Test]
    public function admin_tidak_bisa_menghapus_akunnya_sendiri_lewat_rute_guru_siswa(): void
    {
        $this->delete(route('admin.guru.destroy', $this->admin))->assertNotFound();
        $this->assertModelExists($this->admin);
    }

    // --------------------------------------------------------- kelas, mapel

    #[Test]
    public function admin_mengelola_kelas(): void
    {
        $this->post(route('admin.kelas.store'), ['tingkat' => 'VII', 'nama' => 'VII A', 'tahun_ajaran' => '2026/2027'])
            ->assertRedirect();
        $kelas = Kelas::firstOrFail();

        $this->post(route('admin.kelas.store'), ['tingkat' => 'VII', 'nama' => 'VII A', 'tahun_ajaran' => '2026/2027'])
            ->assertSessionHasErrors('nama');
        $this->post(route('admin.kelas.store'), ['tingkat' => 'VII', 'nama' => 'VII A', 'tahun_ajaran' => '2027/2028'])
            ->assertSessionHasNoErrors();
        $this->post(route('admin.kelas.store'), ['tingkat' => 'X', 'nama' => 'X A', 'tahun_ajaran' => '2026/2027'])
            ->assertSessionHasErrors('tingkat');
        $this->post(route('admin.kelas.store'), ['tingkat' => 'VII', 'nama' => 'VII C', 'tahun_ajaran' => '2026-2027'])
            ->assertSessionHasErrors('tahun_ajaran');

        $this->put(route('admin.kelas.update', $kelas), ['tingkat' => 'VII', 'nama' => 'VII Unggulan', 'tahun_ajaran' => '2026/2027'])
            ->assertRedirect();
        $this->assertSame('VII Unggulan', $kelas->fresh()->nama);

        $this->delete(route('admin.kelas.destroy', $kelas))->assertRedirect(route('admin.kelas.index'));
        $this->assertModelMissing($kelas);
    }

    #[Test]
    public function kelas_yang_punya_penugasan_tidak_bisa_dihapus_dan_kelas_siswa_menjadi_kosong_bila_dihapus(): void
    {
        $s = $this->sekolah();

        $this->delete(route('admin.kelas.destroy', $s->kelasA))->assertSessionHas('galat');
        $this->assertModelExists($s->kelasA);

        $kosong = $this->kelas('IX C');
        $siswa = $this->siswa($kosong);
        $this->delete(route('admin.kelas.destroy', $kosong))->assertRedirect();
        $this->assertNull($siswa->fresh()->kelas_id);
    }

    #[Test]
    public function admin_mengelola_mata_pelajaran(): void
    {
        $this->post(route('admin.mata-pelajaran.store'), ['kode' => 'IPA', 'nama' => 'IPA'])->assertRedirect();
        $this->post(route('admin.mata-pelajaran.store'), ['kode' => 'IPA', 'nama' => 'Lain'])->assertSessionHasErrors('kode');
        $this->post(route('admin.mata-pelajaran.store'), ['kode' => 'huruf kecil', 'nama' => 'X'])->assertSessionHasErrors('kode');

        $mapel = MataPelajaran::firstOrFail();
        $this->put(route('admin.mata-pelajaran.update', $mapel), ['kode' => 'IPA', 'nama' => 'Ilmu Pengetahuan Alam'])->assertRedirect();
        $this->assertSame('Ilmu Pengetahuan Alam', $mapel->fresh()->nama);

        $this->delete(route('admin.mata-pelajaran.destroy', $mapel))->assertRedirect();
        $this->assertModelMissing($mapel);
    }

    #[Test]
    public function mata_pelajaran_yang_dipakai_penugasan_tidak_bisa_dihapus(): void
    {
        $s = $this->sekolah();

        $this->delete(route('admin.mata-pelajaran.destroy', $s->ipa))->assertSessionHas('galat');
        $this->assertModelExists($s->ipa);
    }

    // ------------------------------------------------ penugasan & penempatan

    #[Test]
    public function admin_menugaskan_guru_dan_menolak_penugasan_ganda(): void
    {
        $guru = $this->guru();
        $kelas = $this->kelas();
        $mapel = $this->mapel('Matematika');
        $data = ['guru_id' => $guru->id, 'kelas_id' => $kelas->id, 'mata_pelajaran_id' => $mapel->id];

        $this->post(route('admin.penugasan-guru.store'), $data)->assertRedirect();
        $this->assertSame(1, PenugasanGuru::count());

        $lain = $this->guru();
        $this->post(route('admin.penugasan-guru.store'), ['guru_id' => $lain->id] + $data)
            ->assertSessionHasErrors('mata_pelajaran_id');
        $this->assertSame(1, PenugasanGuru::count());
    }

    #[Test]
    public function penugasan_hanya_untuk_guru_aktif(): void
    {
        $nonaktif = $this->guru(['aktif' => false]);
        $siswa = $this->siswa();
        $kelas = $this->kelas();
        $mapel = $this->mapel();

        foreach ([$nonaktif, $siswa] as $bukanGuruAktif) {
            $this->post(route('admin.penugasan-guru.store'), ['guru_id' => $bukanGuruAktif->id, 'kelas_id' => $kelas->id, 'mata_pelajaran_id' => $mapel->id])
                ->assertSessionHasErrors('guru_id');
        }
    }

    #[Test]
    public function penugasan_yang_sudah_punya_materi_tidak_bisa_dihapus(): void
    {
        $s = $this->sekolah();
        $this->materi($s->ipaA);

        $this->delete(route('admin.penugasan-guru.destroy', $s->ipaA))->assertSessionHas('galat');
        $this->assertModelExists($s->ipaA);

        $this->delete(route('admin.penugasan-guru.destroy', $s->ipaB))->assertSessionHas('sukses');
        $this->assertModelMissing($s->ipaB);
    }

    #[Test]
    public function admin_menempatkan_dan_mengeluarkan_siswa_dari_kelas(): void
    {
        $kelas = $this->kelas();
        $a = $this->siswa();
        $b = $this->siswa();
        $guru = $this->guru();

        $this->post(route('admin.penempatan-siswa.store'), ['kelas_id' => $kelas->id, 'siswa_ids' => [$a->id, $b->id, $guru->id]])
            ->assertSessionHasErrors('siswa_ids.2');

        $this->post(route('admin.penempatan-siswa.store'), ['kelas_id' => $kelas->id, 'siswa_ids' => [$a->id, $b->id]])
            ->assertRedirect();
        $this->assertSame($kelas->id, $a->fresh()->kelas_id);
        $this->assertSame($kelas->id, $b->fresh()->kelas_id);

        $this->post(route('admin.penempatan-siswa.store'), ['kelas_id' => $kelas->id])->assertSessionHasErrors('siswa_ids');

        $this->delete(route('admin.penempatan-siswa.destroy', $a))->assertRedirect();
        $this->assertNull($a->fresh()->kelas_id);
    }

    #[Test]
    public function memindahkan_siswa_mengubah_mata_pelajaran_yang_ia_lihat(): void
    {
        $s = $this->sekolah();
        $this->materi($s->ipaB, ['judul' => 'Materi Kelas B']);

        $this->actingAs($s->siswaA1)->get(route('siswa.mata-pelajaran.index'))->assertDontSee('Pak Dodi');

        $this->actingAs($this->admin)->post(route('admin.penempatan-siswa.store'), ['kelas_id' => $s->kelasB->id, 'siswa_ids' => [$s->siswaA1->id]]);

        $this->actingAs($s->siswaA1->fresh())->get(route('siswa.mata-pelajaran.index'))->assertSee('Pak Dodi')->assertDontSee('Bu Rina');
    }
}
