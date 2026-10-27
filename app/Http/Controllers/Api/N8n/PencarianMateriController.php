<?php

namespace App\Http\Controllers\Api\N8n;

use App\Http\Controllers\Controller;
use App\Http\Resources\BagianMateriResource;
use App\Models\BagianMateri;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PencarianMateriController extends Controller
{
    /** Kata tanya umum yang tidak berguna untuk pencarian FULLTEXT. */
    private const KATA_BUANG = [
        // kata tanya dan perintah
        'apa', 'apakah', 'siapa', 'kapan', 'dimana', 'mana', 'berapa', 'bagaimana', 'jelaskan',
        'sebutkan', 'mengapa', 'kenapa', 'tolong', 'bisakah', 'bolehkah',
        // kata tugas yang ada di hampir setiap kalimat
        'yang', 'dan', 'dengan', 'adalah', 'itu', 'ini', 'dari', 'untuk', 'pada', 'dalam', 'oleh',
        'atau', 'ada', 'akan', 'bisa', 'dapat', 'saya', 'aku', 'kamu', 'kita', 'kami', 'mereka',
        'tentang', 'seperti', 'sebagai', 'secara', 'para', 'agar', 'juga', 'sudah', 'telah', 'saat',
    ];

    /**
     * Cari bagian materi yang BOLEH diakses pengguna (Workflow 2, node "Cari Materi").
     * Hak akses ditentukan Laravel dari user_id; n8n tidak pernah mengirim kelas_id,
     * jadi tidak bisa meminta materi kelas lain.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'q' => ['required', 'string', 'min:3', 'max:500'],
            'limit' => ['nullable', 'integer', 'between:1,8'],
        ]);

        $user = User::aktif()->find($data['user_id']);

        $kata = collect(preg_split('/\W+/u', mb_strtolower($data['q']), -1, PREG_SPLIT_NO_EMPTY))
            ->reject(fn ($k) => mb_strlen($k) < 3 || in_array($k, self::KATA_BUANG, true))
            ->unique()
            ->implode(' ');

        if (! $user || $kata === '') {
            return BagianMateriResource::collection(collect());
        }

        $hasil = BagianMateri::query()
            ->untukPengguna($user)
            ->cocokDengan($kata)
            ->with('berkasMateri.materi.penugasanGuru.mataPelajaran')
            ->limit($data['limit'] ?? 5)
            ->get();

        return BagianMateriResource::collection($hasil);
    }
}
