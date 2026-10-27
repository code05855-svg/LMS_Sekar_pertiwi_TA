<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Mata pelajaran | DFD D1.3.
 *
 * @property int $id
 * @property string $kode
 * @property string $nama
 */
class MataPelajaran extends Model
{
    protected $table = 'mata_pelajaran';

    protected $fillable = [
        'kode',
        'nama',
    ];

    /** Guru dan kelas yang mengampu mapel ini. */
    public function penugasanGuru(): HasMany
    {
        return $this->hasMany(PenugasanGuru::class, 'mata_pelajaran_id');
    }
}
