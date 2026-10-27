<?php

namespace App\Http\Controllers\Api\N8n;

use App\Http\Controllers\Controller;
use App\Models\BerkasMateri;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BerkasMateriController extends Controller
{
    /** n8n mengunduh PDF untuk diekstrak (Workflow 1, node "Ambil PDF"). */
    public function show(BerkasMateri $berkas): StreamedResponse
    {
        $disk = Storage::disk('materi');

        abort_unless($disk->exists($berkas->path), 404, 'Berkas tidak ditemukan');

        return $disk->download($berkas->path, $berkas->nama_asli);
    }
}
