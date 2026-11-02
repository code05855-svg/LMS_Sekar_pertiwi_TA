<?php 

namespace App\Models;

use App\Enums\JenisJawaban;
use App\Support\BerkasAman;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lampiran dari guru pada sebuah tugas: dokumen, foto, atau tautan.
 *
 * @property int $id
 * @property int $tugas_id
 * @property JenisJawaban $jenis
 * @property string|null $judul
 * @property string|null $nama_asli
 * @property string|null $path
 * @property string|null $mime
 * @property int|null $ukuran
 * @property string|null $tautan
 * @property-read Tugas $tugas
 */

class LampiranTugas extends Model 
{
    protected $table = 'lampiran_tugas';

    protected $fillable = ['tugas_id','jenis','judul','nama_asli','path', 'mime','ukuran','tautan'];

    protected function casts(): array 
    {
        return [
            'jenis' => JenisJawaban::class,
            'ukuran' => 'integer',
        ];
    }

    public function tugas(): BelongsTo
    {
        return $this->belongsTo(Tugas::class, 'tugas_id');
    }

    public function namaTampil(): string
    {
        if ($this->jenis === JenisJawaban::Tautan) {
            return $this->judul ?: ($this->hostTautan() ?? 'Tautan');
            }
        return $this->nama_asli ?: 'Berkas';
    }

    public function hostTautan(): ?string 
    {
        $host = $this->tautan ? parse_url ($this->tautan, PHP_URL_HOST) : null;

        return $host ? preg_replace('/^www\./i','',$host): null;
    }

    public function bisaDilihatLangsung(): bool
    {
        return BerkasAman::bisaLangsung($this->mime);
    }
}