<?php

namespace App\Policies;

use App\Models\ForumBalasan;
use App\Models\User;

class ForumBalasanPolicy
{
    public function update(User $user, ForumBalasan $balasan): bool
    {
        return (int) $balasan->user_id === (int) $user->id
            && $balasan->topik->penugasanGuru->bolehDiaksesOleh($user)
            && ! $balasan->topik->dikunci;
    }

    public function delete(User $user, ForumBalasan $balasan): bool
    {
        $penugasan = $balasan->topik->penugasanGuru;

        if (! $penugasan->bolehDiaksesOleh($user)) {
            return false;
        }

        return (int) $balasan->user_id === (int) $user->id || $user->adalahGuru();
    }
}
