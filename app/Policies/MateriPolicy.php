<?php

namespace App\Policies;

use App\Models\Materi;
use App\Models\User;

/**
 * Guru hanya mengelola materi pada kelas dan mapel yang ia ampu.
 * Siswa hanya melihat materi TERBIT pada kelasnya sendiri.
 * Admin tidak punya akses ke materi.
 */
class MateriPolicy
{
    public function view(User $user, Materi $materi): bool
    {
        if ($user->adalahGuru()) {
            return $this->milikGuru($user, $materi);
        }

        if ($user->adalahSiswa()) {
            return $materi->adalahTerbit() && $materi->penugasanGuru->bolehDiaksesOleh($user);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->adalahGuru();
    }

    public function update(User $user, Materi $materi): bool
    {
        return $user->adalahGuru() && $this->milikGuru($user, $materi);
    }

    public function delete(User $user, Materi $materi): bool
    {
        return $this->update($user, $materi);
    }

    private function milikGuru(User $user, Materi $materi): bool
    {
        return (int) $materi->penugasanGuru->guru_id === (int) $user->id;
    }
}
