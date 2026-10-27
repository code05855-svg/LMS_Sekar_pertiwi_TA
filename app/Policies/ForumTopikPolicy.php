<?php

namespace App\Policies;

use App\Models\ForumTopik;
use App\Models\PenugasanGuru;
use App\Models\User;

/**
 * Forum per kelas dan mapel (penugasan_guru). Guru pengampu dan siswa kelas
 * itu boleh membaca dan membuat topik. Pembuat mengubah/menghapus kirimannya
 * sendiri; guru pengampu menghapus, menyematkan, dan mengunci topik siapa pun.
 */
class ForumTopikPolicy
{
    public function view(User $user, ForumTopik $topik): bool
    {
        return $topik->penugasanGuru->bolehDiaksesOleh($user);
    }

    /** Dipanggil: authorize('create', [ForumTopik::class, $penugasan]) */
    public function create(User $user, PenugasanGuru $penugasan): bool
    {
        return $penugasan->bolehDiaksesOleh($user);
    }

    public function update(User $user, ForumTopik $topik): bool
    {
        return (int) $topik->user_id === (int) $user->id && $this->view($user, $topik);
    }

    public function delete(User $user, ForumTopik $topik): bool
    {
        return $this->update($user, $topik) || $this->moderasi($user, $topik);
    }

    /** Sematkan dan kunci: hanya guru pengampu. */
    public function moderasi(User $user, ForumTopik $topik): bool
    {
        return $user->adalahGuru() && $topik->penugasanGuru->bolehDiaksesOleh($user);
    }

    /** Balasan hanya pada topik yang tidak dikunci. */
    public function balas(User $user, ForumTopik $topik): bool
    {
        return ! $topik->dikunci && $this->view($user, $topik);
    }
}
