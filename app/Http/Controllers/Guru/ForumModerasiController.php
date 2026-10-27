<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\ForumTopik;
use Illuminate\Http\RedirectResponse;

/** Moderasi forum oleh guru pengampu: sematkan dan kunci (hapus lewat ForumTopikController). */
class ForumModerasiController extends Controller
{
    public function sematkan(ForumTopik $topik): RedirectResponse
    {
        $this->authorize('moderasi', $topik);

        $topik->sematkan(! $topik->disematkan);

        return back()->with('sukses', $topik->disematkan ? 'Topik disematkan di atas.' : 'Sematan topik dilepas.');
    }

    public function kunci(ForumTopik $topik): RedirectResponse
    {
        $this->authorize('moderasi', $topik);

        $topik->kunci(! $topik->dikunci);

        return back()->with('sukses', $topik->dikunci ? 'Topik dikunci. Balasan baru tidak dapat ditambahkan.' : 'Topik dibuka kembali.');
    }
}
