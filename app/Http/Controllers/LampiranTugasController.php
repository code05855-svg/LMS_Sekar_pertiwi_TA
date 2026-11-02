<?php

namespace App\Http\Controllers;

use App\Models\LampiranTugas;
use App\Support\BerkasAman;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LampiranTugasController extends Controller
{
    public function show(Request $request, LampiranTugas $lampiran): StreamedResponse
    {
        $lampiran->loadMissing('tugas.penugasanGuru');
        $this->authorize('view', $lampiran->tugas);

        abort_if($lampiran->tautan !== null, 404);

        return BerkasAman::kirim('lampiran_tugas', $lampiran->path, (string) $lampiran->nama_asli, $lampiran->mime, $request->boolean('lihat'));
    }
}
