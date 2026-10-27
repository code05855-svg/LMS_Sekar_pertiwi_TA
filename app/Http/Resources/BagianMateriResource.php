<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bentuk JSON bagian materi untuk n8n (Workflow 2, node "Cari Materi").
 *
 * @mixin \App\Models\BagianMateri
 */
class BagianMateriResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $materi = $this->berkasMateri->materi;

        return [
            'id' => $this->id,
            'materi_id' => $materi->id,
            'materi' => $materi->judul,
            'mapel' => $materi->penugasanGuru->mataPelajaran->nama,
            'berkas_id' => $this->berkasMateri->id,
            'berkas' => $this->berkasMateri->nama_asli,
            'halaman' => $this->halaman,
            'teks' => $this->teks,
            'skor' => round((float) ($this->skor ?? 0), 3),
        ];
    }
}
