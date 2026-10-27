<?php

namespace App\Policies;

use App\Models\PengumpulanTugas;
use App\Models\User;

/**
 * Berkas jawaban: hanya siswa pemiliknya dan guru pengampu tugas itu yang
 * boleh melihat/mengunduh. Siswa boleh mengganti atau menarik jawabannya
 * selama belum dinilai. Hanya guru pengampu yang boleh menilai.
 */
class PengumpulanTugasPolicy
{
    public function view(User $user, PengumpulanTugas $pengumpulan): bool
    {
        if ($user->adalahSiswa()) {
            return (int) $pengumpulan->siswa_id === (int) $user->id;
        }

        return $this->guruPengampu($user, $pengumpulan);
    }

    public function update(User $user, PengumpulanTugas $pengumpulan): bool
    {
        return $user->adalahSiswa()
            && (int) $pengumpulan->siswa_id === (int) $user->id
            && ! $pengumpulan->telahDinilai()
            && $pengumpulan->tugas->penugasanGuru->bolehDiaksesOleh($user);
    }

    public function delete(User $user, PengumpulanTugas $pengumpulan): bool
    {
        return $this->update($user, $pengumpulan);
    }

    public function nilai(User $user, PengumpulanTugas $pengumpulan): bool
    {
        return $this->guruPengampu($user, $pengumpulan);
    }

    private function guruPengampu(User $user, PengumpulanTugas $pengumpulan): bool
    {
        return $user->adalahGuru()
            && $pengumpulan->tugas->penugasanGuru->bolehDiaksesOleh($user);
    }
}
