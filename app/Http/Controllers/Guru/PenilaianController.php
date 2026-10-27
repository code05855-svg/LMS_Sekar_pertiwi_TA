<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateNilaiRequest;
use App\Models\PengumpulanTugas;
use Illuminate\Http\RedirectResponse;

/** Guru memberi nilai dan komentar (proses 3.6). */
class PenilaianController extends Controller
{
    public function update(UpdateNilaiRequest $request, PengumpulanTugas $pengumpulan): RedirectResponse
    {
        $this->authorize('nilai', $pengumpulan);

        $pengumpulan->beriNilai($request->validated('nilai'), $request->validated('komentar'));

        // Tombol "Simpan dan buka berikutnya": lanjut ke jawaban lain yang belum dinilai.
        if ($request->input('lanjut') === 'berikutnya') {
            $berikutnya = PengumpulanTugas::query()
                ->where('tugas_id', $pengumpulan->tugas_id)
                ->belumDinilai()
                ->orderBy('waktu_kumpul')
                ->first();

            if ($berikutnya) {
                return redirect()->route('guru.pengumpulan-tugas.show', $berikutnya)
                    ->with('sukses', 'Nilai tersimpan. Berikut jawaban berikutnya yang belum dinilai.');
            }

            return redirect()->route('guru.pengumpulan-tugas.index', $pengumpulan->tugas_id)
                ->with('sukses', 'Nilai tersimpan. Semua jawaban sudah dinilai.');
        }

        return redirect()->route('guru.pengumpulan-tugas.show', $pengumpulan)
            ->with('sukses', 'Nilai dan komentar tersimpan.');
    }
}
