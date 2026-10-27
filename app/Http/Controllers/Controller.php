<?php

namespace App\Http\Controllers;

use App\Models\PenugasanGuru;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests;

    /**
     * Guru pengampu dan siswa di kelas itu boleh mengakses penugasan
     * (kelas + mapel); selain itu 403. Admin tidak termasuk.
     */
    protected function pastikanAkses(PenugasanGuru $penugasan): PenugasanGuru
    {
        abort_unless($penugasan->bolehDiaksesOleh(auth()->user()), 403, 'Anda tidak punya akses ke kelas dan mata pelajaran ini.');

        return $penugasan;
    }

    /** Isian formulir sebagai teks; array atau nilai aneh menjadi '' (lalu ditolak validasi). */
    protected function teks(Request $request, string $kunci): string
    {
        $nilai = $request->input($kunci);

        return is_scalar($nilai) ? trim((string) $nilai) : '';
    }
}
