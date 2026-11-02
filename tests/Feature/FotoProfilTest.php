<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FotoProfilTest extends TestCase
{
    #[Test]
    public function foto_profil_muncul_di_header_admin_guru_dan_siswa(): void
    {
        Storage::fake('foto_profil');

        $pengguna = [$this->admin(), $this->guru(), $this->siswa()];

        foreach ($pengguna as $user) {
            $this->simpanFotoProfil($user);

            $this->actingAs($user)
                ->get(route('profil.edit'))
                ->assertOk()
                ->assertSee($user->fotoProfilUrl(), false);
        }
    }

    #[Test]
    public function admin_dan_guru_dapat_melihat_foto_siswa_di_daftar_akun(): void
    {
        Storage::fake('foto_profil');

        $admin = $this->admin();
        $guru = $this->guru();
        $siswa = $this->siswa();
        $this->simpanFotoProfil($guru);
        $this->simpanFotoProfil($siswa);

        $this->actingAs($admin)
            ->get(route('admin.guru.index'))
            ->assertOk()
            ->assertSee($guru->fotoProfilUrl(), false);

        $this->get(route('admin.siswa.index'))
            ->assertOk()
            ->assertSee($siswa->fotoProfilUrl(), false);
    }

    #[Test]
    public function foto_profil_dapat_dilihat_semua_peran_yang_sudah_login(): void
    {
        Storage::fake('foto_profil');

        $pemilik = $this->siswa();
        $this->simpanFotoProfil($pemilik);
        $pemirsa = [$this->admin(), $this->guru(), $this->siswa()];

        foreach ($pemirsa as $user) {
            $this->actingAs($user)
                ->get(route('foto-profil.show', $pemilik))
                ->assertOk()
                ->assertHeader('content-type', 'image/jpeg')
                ->assertHeader('x-content-type-options', 'nosniff');
        }

        $this->actingAs($pemilik)
            ->get(route('foto-profil.show', $pemilik))
            ->assertOk();
    }

    private function simpanFotoProfil(User $user): void
    {
        $path = $user->id.'-foto.jpg';
        Storage::disk('foto_profil')->put($path, 'foto uji');
        $user->forceFill(['foto_profil' => $path])->save();
    }
}
