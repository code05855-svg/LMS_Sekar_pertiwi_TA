<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\PenugasanGuru;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Penempatan guru: satu guru mengampu satu mapel pada satu kelas. */
class PenugasanGuruController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.penugasan-guru.index', [
            'daftar' => PenugasanGuru::query()
                ->with(['guru', 'kelas', 'mataPelajaran'])
                ->when($request->filled('kelas'), fn ($q) => $q->where('kelas_id', $request->query('kelas')))
                ->when($request->filled('guru'), fn ($q) => $q->where('guru_id', $request->query('guru')))
                ->orderBy('kelas_id')
                ->orderBy('mata_pelajaran_id')
                ->paginate(15)
                ->withQueryString(),
            'daftarKelas' => Kelas::urut()->get(),
            'daftarGuru' => User::guru()->aktif()->orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.penugasan-guru.create', [
            'daftarGuru' => User::guru()->aktif()->orderBy('nama')->get(['id', 'nama', 'nomor_induk']),
            'daftarKelas' => Kelas::urut()->get(),
            'daftarMapel' => MataPelajaran::orderBy('nama')->get(),
            'pilihan' => $request->only(['kelas_id', 'guru_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'guru_id' => ['required', 'integer', Rule::exists('users', 'id')->where('peran', 'guru')->where('aktif', true)],
            'kelas_id' => ['required', 'integer', Rule::exists('kelas', 'id')],
            'mata_pelajaran_id' => [
                'required', 'integer', Rule::exists('mata_pelajaran', 'id'),
                Rule::unique('penugasan_guru', 'mata_pelajaran_id')->where('kelas_id', $this->teks($request, 'kelas_id')),
            ],
        ], [
            'guru_id.exists' => 'Pilih akun guru yang aktif.',
            'mata_pelajaran_id.unique' => 'Mata pelajaran ini sudah punya guru pengampu di kelas tersebut.',
        ]);

        PenugasanGuru::create($data);

        return redirect()->route('admin.penugasan-guru.index', ['kelas' => $data['kelas_id']])
            ->with('sukses', 'Penugasan guru berhasil disimpan.');
    }

    public function destroy(PenugasanGuru $penugasan): RedirectResponse
    {
        try {
            $penugasan->delete();
        } catch (QueryException) {
            return back()->with('galat', 'Penugasan tidak dapat dihapus karena guru sudah membuat materi, tugas, atau topik forum '
                .'pada kelas ini.');
        }

        return back()->with('sukses', 'Penugasan guru berhasil dihapus.');
    }
}
