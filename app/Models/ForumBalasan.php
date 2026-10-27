<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Balasan satu tingkat (datar) pada topik forum | DFD D4.2.
 * Tidak diindeks chatbot.
 *
 * @property int $id
 * @property int $forum_topik_id
 * @property int $user_id
 * @property string $isi
 * @property-read ForumTopik $topik
 * @property-read User $pembuat
 */
class ForumBalasan extends Model
{
    protected $table = 'forum_balasan';

    protected $fillable = [
        'forum_topik_id',
        'user_id',
        'isi',
    ];

    public function topik(): BelongsTo
    {
        return $this->belongsTo(ForumTopik::class, 'forum_topik_id');
    }

    /** Pembuat balasan (guru atau siswa). */
    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
