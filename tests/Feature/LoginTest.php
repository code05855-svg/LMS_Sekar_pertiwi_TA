<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginTest extends TestCase
{
    #[Test]
    public function halaman_login_tampil_dan_beranda_mengarah_ke_sana(): void
    {
        $this->get('/')->assertRedirect();
        $this->get(route('login'))->assertOk()->assertSee('Masuk');
    }

    #[Test]
    public function login_dengan_nomor_induk_membawa_ke_dashboard(): void
    {
        $guru = $this->guru(['nomor_induk' => '198501012010', 'password' => bcrypt('rahasia123')]);

        $this->post(route('login.store'), ['nomor_induk' => '198501012010', 'password' => 'rahasia123'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($guru);
    }

    #[Test]
    public function login_dengan_email_juga_diterima(): void
    {
        $siswa = $this->siswa(null, ['email' => 'alya@sekolah.test', 'password' => bcrypt('rahasia123')]);

        $this->post(route('login.store'), ['nomor_induk' => 'alya@sekolah.test', 'password' => 'rahasia123'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($siswa);
    }

    #[Test]
    public function kata_sandi_salah_ditolak_tanpa_membocorkan_apa_yang_salah(): void
    {
        $this->guru(['nomor_induk' => '1985', 'password' => bcrypt('rahasia123')]);

        $this->from(route('login'))->post(route('login.store'), ['nomor_induk' => '1985', 'password' => 'salah'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('nomor_induk');
        $this->from(route('login'))->post(route('login.store'), ['nomor_induk' => 'tidak-ada', 'password' => 'salah'])
            ->assertSessionHasErrors('nomor_induk');

        $this->assertGuest();
    }

    #[Test]
    public function akun_nonaktif_tidak_bisa_masuk(): void
    {
        $this->guru(['nomor_induk' => '1985', 'password' => bcrypt('rahasia123'), 'aktif' => false]);

        $this->post(route('login.store'), ['nomor_induk' => '1985', 'password' => 'rahasia123'])
            ->assertSessionHasErrors('nomor_induk');

        $this->assertGuest();
    }

    #[Test]
    public function akun_yang_dinonaktifkan_saat_sedang_masuk_langsung_dikeluarkan(): void
    {
        $guru = $this->guru();
        $this->actingAs($guru)->get(route('dashboard'))->assertOk();

        $guru->update(['aktif' => false]);

        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    #[Test]
    public function isian_kosong_ditolak(): void
    {
        $this->post(route('login.store'), [])->assertSessionHasErrors(['nomor_induk', 'password']);
    }

    #[Test]
    public function keluar_menghapus_sesi(): void
    {
        $this->actingAs($this->siswa())->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
    }

    #[Test]
    public function pengguna_yang_sudah_masuk_tidak_melihat_halaman_login_lagi(): void
    {
        $this->actingAs($this->guru())->get(route('login'))->assertRedirect(route('dashboard'));
    }

    #[Test]
    public function seeder_admin_membuat_akun_dari_env_dan_bisa_dijalankan_ulang(): void
    {
        Artisan::call('db:seed', ['--class' => AdminSeeder::class, '--force' => true]);
        Artisan::call('db:seed', ['--class' => AdminSeeder::class, '--force' => true]);

        $this->assertSame(1, User::where('nomor_induk', 'admin')->count());
        $this->post(route('login.store'), ['nomor_induk' => 'admin', 'password' => 'admin12345'])
            ->assertRedirect(route('dashboard'));
    }

    #[Test]
    public function halaman_profil_mengubah_kata_sandi_hanya_bila_sandi_lama_benar(): void
    {
        $siswa = $this->siswa(null, ['password' => bcrypt('lama12345')]);

        $this->actingAs($siswa)->put(route('profil.update'), [
            'password_saat_ini' => 'salah', 'password' => 'baru123456', 'password_confirmation' => 'baru123456',
        ])->assertSessionHasErrors();

        $this->put(route('profil.update'), [
            'password_saat_ini' => 'lama12345', 'password' => 'baru123456', 'password_confirmation' => 'baru123456',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('baru123456', $siswa->fresh()->password));
    }
}
