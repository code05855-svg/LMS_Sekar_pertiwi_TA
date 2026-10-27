<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTugasRequest;
use App\Models\PenugasanGuru;
use App\Models\Tugas;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TugasController extends Controller
{
    public function index(Request $request): View
    {
        $guru = $request->user();

        return view('guru.tugas.index', [
            'daftar' => Tugas::query()
                ->diaksesOleh($guru)
                ->with(['penugasanGuru.kelas', 'penugasanGuru.mataPelajaran'])
                ->withCount('pengumpulanTugas')
                ->withCount(['pengumpulanTugas as belum_dinilai_count' => fn ($q) => $q->belumDinilai()])
                ->when($request->filled('penugasan'), fn ($q) => $q->where('penugasan_guru_id', $request->query('penugasan')))
                ->when(filled($request->query('cari')), fn ($q) => $q->where('judul', 'like', '%'.trim($request->query('cari')).'%'))
                ->orderByDesc('tenggat')
                ->paginate(10)
                ->withQueryString(),
            'daftarPenugasan' => $this->penugasanGuru($guru),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Tugas::class);

        return view('guru.tugas.create', [
            'tugas' => new Tugas([
                'penugasan_guru_id' => $request->query('penugasan'),
                'tenggat' => now()->addDays(7)->setTime(23, 59),
                'nilai_maksimum' => 100,
            ]),
            'daftarPenugasan' => $this->penugasanGuru($request->user()),
        ]);
    }

    public function store(StoreTugasRequest $request): RedirectResponse
    {
        $this->authorize('create', Tugas::class);

        $tugas = Tugas::create($request->validated());

        return redirect()->route('guru.tugas.show', $tugas)->with('sukses', 'Tugas berhasil dibuat.');
    }

    public function show(Tugas $tugas): View
    {
        $this->authorize('view', $tugas);

        $tugas->load(['penugasanGuru.kelas', 'penugasanGuru.mataPelajaran']);
        $jumlahSiswa = User::siswa()->where('kelas_id', $tugas->penugasanGuru->kelas_id)->count();

        $pengumpulan = $tugas->pengumpulanTugas();

        return view('guru.tugas.show', [
            'tugas' => $tugas,
            'ringkasan' => [
                'siswa' => $jumlahSiswa,
                'dikumpulkan' => (clone $pengumpulan)->count(),
                'terlambat' => (clone $pengumpulan)->where('status', 'terlambat')->count(),
                'dinilai' => (clone $pengumpulan)->sudahDinilai()->count(),
                'rata_rata' => (clone $pengumpulan)->sudahDinilai()->avg('nilai'),
            ],
        ]);
    }

    public function edit(Tugas $tugas): View
    {
        $this->authorize('update', $tugas);

        $tugas->load(['penugasanGuru.kelas', 'penugasanGuru.mataPelajaran']);

        return view('guru.tugas.edit', ['tugas' => $tugas]);
    }

    public function update(StoreTugasRequest $request, Tugas $tugas): RedirectResponse
    {
        $this->authorize('update', $tugas);

        $tugas->update($request->safe()->only(['judul', 'instruksi', 'tenggat', 'nilai_maksimum']));

        return redirect()->route('guru.tugas.show', $tugas)->with('sukses', 'Tugas berhasil diperbarui.');
    }

    public function destroy(Tugas $tugas): RedirectResponse
    {
        $this->authorize('delete', $tugas);

        $penugasanId = $tugas->penugasan_guru_id;

        DB::transaction(function () use ($tugas) {
            // Berkas jawaban siswa ikut dihapus dari penyimpanan.
            $tugas->pengumpulanTugas()->pluck('path')->each(fn ($path) => Storage::disk('pengumpulan')->delete($path));
            $tugas->delete();
        });

        return redirect()->route('guru.kelas.show', $penugasanId)->with('sukses', 'Tugas beserta pengumpulannya dihapus.');
    }

    private function penugasanGuru($guru)
    {
        return PenugasanGuru::query()
            ->diaksesOleh($guru)
            ->with(['kelas', 'mataPelajaran'])
            ->get()
            ->sortBy(fn ($p) => $p->kelas->nama.$p->mataPelajaran->nama)
            ->values();
    }
}
