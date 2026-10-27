<?php

namespace App\Http\Controllers\Api\N8n;

use App\Enums\StatusIndeks;
use App\Http\Controllers\Controller;
use App\Models\BerkasMateri;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BagianMateriController extends Controller
{
    /**
     * n8n mengirim hasil ekstraksi. Bagian lama dihapus dan yang baru disimpan
     * dalam satu transaksi, jadi pengindeksan ulang aman dijalankan berkali-kali.
     */
    public function store(Request $request, BerkasMateri $berkas): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([StatusIndeks::Terindeks->value, StatusIndeks::Gagal->value])],
            'pesan' => ['nullable', 'string', 'max:250'],
            'jumlah_halaman' => ['nullable', 'integer', 'min:0'],
            'bagian' => ['required_if:status,terindeks', 'array'],
            'bagian.*.nomor_bagian' => ['required', 'integer', 'min:0'],
            'bagian.*.halaman' => ['nullable', 'integer', 'min:0'],
            'bagian.*.teks' => ['required', 'string'],
        ]);

        DB::transaction(function () use ($berkas, $data) {
            $berkas->bagianMateri()->delete();

            if ($data['status'] === StatusIndeks::Terindeks->value) {
                foreach (array_chunk($data['bagian'], 200) as $kelompok) {
                    $berkas->bagianMateri()->createMany($kelompok);
                }

                $berkas->tandaiTerindeks($data['jumlah_halaman'] ?? null);
            } else {
                $berkas->tandaiGagal($data['pesan'] ?? 'Gagal diindeks');
            }
        });

        return response()->json([
            'ok' => true,
            'jumlah_bagian' => $berkas->bagianMateri()->count(),
        ]);
    }

    /** Mengosongkan indeks satu berkas (opsional, dipakai manual). */
    public function destroy(BerkasMateri $berkas): JsonResponse
    {
        $berkas->bagianMateri()->delete();
        $berkas->tandaiMenunggu();

        return response()->json(['ok' => true]);
    }
}
