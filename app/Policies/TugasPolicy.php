<?php

namespace App\Policies;

use App\Models\Tugas;
use App\Models\User;

/**
 * Guru mengelola tugas pada kelas dan mapel yang ia ampu; siswa hanya
 * melihat dan mengumpulkan tugas kelasnya sendiri.
 */
class TugasPolicy
{
    public function view(User $user, Tugas $tugas): bool
    {
        return $tugas->penugasanGuru->bolehDiaksesOleh($user);
    }

    public function create(User $user): bool
    {
        return $user->adalahGuru();
    }

    public function update(User $user, Tugas $tugas): bool
    {
        return $user->adalahGuru() && $tugas->penugasanGuru->bolehDiaksesOleh($user);
    }

    public function delete(User $user, Tugas $tugas): bool
    {
        return $this->update($user, $tugas);
    }

    /** Siswa di kelas tugas ini boleh mengumpulkan jawaban. */
    public function kumpul(User $user, Tugas $tugas): bool
    {
        return $user->adalahSiswa() && $tugas->penugasanGuru->bolehDiaksesOleh($user);
    }
}
