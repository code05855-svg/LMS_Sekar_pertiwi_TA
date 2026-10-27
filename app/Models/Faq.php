<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * FAQ dasar penggunaan LMS untuk chatbot | DFD D5.2. Diisi lewat FaqSeeder.
 *
 * @property int $id
 * @property string $pertanyaan
 * @property string $jawaban
 * @property string|null $kata_kunci
 * @property bool $aktif
 */
class Faq extends Model
{
    protected $table = 'faq';

    protected $fillable = [
        'pertanyaan',
        'jawaban',
        'kata_kunci',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function scopeAktif(Builder $query): void
    {
        $query->where('aktif', true);
    }

    /**
     * Opsional, bila FAQ sudah banyak (query [6.3] kedua pada SQL): FAQ aktif
     * yang cocok dengan pertanyaan, diurutkan dari skor tertinggi.
     */
    public function scopeCocokDengan(Builder $query, string $kata): void
    {
        $query->select('faq.*')
            ->selectRaw('MATCH(pertanyaan, kata_kunci) AGAINST (? IN NATURAL LANGUAGE MODE) AS skor', [$kata])
            ->whereRaw('MATCH(pertanyaan, kata_kunci) AGAINST (? IN NATURAL LANGUAGE MODE)', [$kata])
            ->orderByDesc('skor');
    }
}
