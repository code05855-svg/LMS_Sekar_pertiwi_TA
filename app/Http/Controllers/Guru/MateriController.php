<?php

namespace App\Http\Controllers\Guru;

use App\Enums\StatusMateri;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMateriRequest;
use App\Models\Materi;
use App\Models\PenugasanGuru;
use App\Services\BerkasMateriService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MateriController extends Controller
{
    public function index(Request $request): View
    {
        $guru = $request->user();
        $status = StatusMateri::tryFrom((string) $request->query('status'));

        return view('guru.materi.index', [
            'daftar' => Materi::query()
                ->untukGuru($guru)
                ->with(['penugasanGuru.kelas', 'penugasanGuru.mataPelajaran'])
                ->withCount('berkasMateri')
                ->withCount(['berkasMateri as berkas_terindeks_count' => fn ($q) => $q->terindeks()])
                ->when($request->filled('penugasan'), fn ($q) => $q->where('penugasan_guru_id', $request->query('penugasan')))
                ->when($status, fn ($q) => $q->where('status', $status))
                ->when(filled($request->query('cari')), fn ($q) => $q->where('judul', 'like', '%'.trim($request->query('cari')).'%'))
                ->latest()
                ->paginate(10)
                ->withQueryString(),
            'daftarPenugasan' => $this->penugasanGuru($guru),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Materi::class);

        return view('guru.materi.create', [
            'materi' => new Materi(['status' => StatusMateri::Draf, 'penugasan_guru_id' => $request->query('penugasan')]),
            'daftarPenugasan' => $this->penugasanGuru($request->user()),
        ]);
    }

    public function store(StoreMateriRequest $request, BerkasMateriService $berkas): RedirectResponse
    {
        $this->authorize('create', Materi::class);

        $materi = DB::transaction(function () use ($request, $berkas) {
            $materi = Materi::create($request->safe()->only(['penugasan_guru_id', 'judul', 'deskripsi', 'status']));

            foreach ($request->file('berkas', []) as $file) {
                $berkas->simpan($materi, $file);
            }

            return $materi;
        });

        return redirect()->route('guru.materi.show', $materi)
            ->with('sukses', 'Materi berhasil disimpan.'.($request->hasFile('berkas') ? ' PDF sedang diindeks untuk chatbot.' : ''));
    }

    public function show(Materi $materi): View
    {
        $this->authorize('view', $materi);

        $materi->load(['penugasanGuru.kelas', 'penugasanGuru.mataPelajaran', 'berkasMateri' => fn ($q) => $q->orderBy('id')]);

        return view('guru.materi.show', ['materi' => $materi]);
    }

    public function edit(Materi $materi): View
    {
        $this->authorize('update', $materi);

        $materi->load(['penugasanGuru.kelas', 'penugasanGuru.mataPelajaran']);

        return view('guru.materi.edit', ['materi' => $materi]);
    }

    public function update(StoreMateriRequest $request, Materi $materi, BerkasMateriService $berkas): RedirectResponse
    {
        $this->authorize('update', $materi);

        DB::transaction(function () use ($request, $materi, $berkas) {
            $materi->update($request->safe()->only(['judul', 'deskripsi', 'status']));

            foreach ($request->file('berkas', []) as $file) {
                $berkas->simpan($materi, $file);
            }
        });

        return redirect()->route('guru.materi.show', $materi)->with('sukses', 'Materi berhasil diperbarui.');
    }

    public function destroy(Materi $materi, BerkasMateriService $berkas): RedirectResponse
    {
        $this->authorize('delete', $materi);

        $penugasanId = $materi->penugasan_guru_id;

        DB::transaction(function () use ($materi, $berkas) {
            // Hapus lewat model agar Observer ikut menghapus file PDF fisiknya.
            $materi->berkasMateri->each(fn ($b) => $berkas->hapus($b));
            $materi->delete();
        });

        return redirect()->route('guru.kelas.show', $penugasanId)->with('sukses', 'Materi berhasil dihapus.');
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
