<?php
 
namespace App\Models;
 
use App\Enums\KategoriInformasi;
use App\Enums\StatusKonten;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
 
/**
 * @property int $id
 * @property string $judul
 * @property string $slug
 * @property KategoriInformasi $kategori
 * @property string $ringkasan
 * @property string $isi
 * @property string|null $gambar        
 * @property StatusKonten $status
 * @property bool $disematkan           
 * @property \Illuminate\Support\Carbon|null $terbit_pada
 * @property int|null $dibuat_oleh
 */
class Informasi extends Model
{
    protected $table = 'informasi';
 
    protected $fillable = [
        'judul',
        'slug',
        'kategori',
        'ringkasan',
        'isi',
        'gambar',
        'status',
        'disematkan',
        'terbit_pada',
        'dibuat_oleh',
    ];
 
    protected function casts(): array
    {
        return [
            'kategori' => KategoriInformasi::class,
            'status' => StatusKonten::class,
            'disematkan' => 'boolean',
            'terbit_pada' => 'datetime',
        ];
    }
 
    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
 
    
 
    public function scopeTerbit(Builder $query): void
    {
        $query->where('status', StatusKonten::Terbit->value);
    }
 
    /** Yang disematkan di atas, lalu yang paling baru terbit. */
    public function scopeUrutBeranda(Builder $query): void
    {
        $query->orderByDesc('disematkan')->orderByDesc('terbit_pada')->orderByDesc('id');
    }
 
 
    public function gambarUrl(): ?string
    {
        return $this->gambar ? route('beranda.gambar', $this->gambar) : null;
    }
 
    public function tanggalTampil(): \Illuminate\Support\Carbon
    {
        return $this->terbit_pada ?? $this->created_at;
    }
 
    /**
     *
     * @return array<int, string>
     */
    public function paragraf(): array
    {
        return array_values(array_filter(
            array_map('trim', preg_split('/\R{2,}/', trim($this->isi)) ?: []),
            fn ($p) => $p !== ''
        ));
    }
}
