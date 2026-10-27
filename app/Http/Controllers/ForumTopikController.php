<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTopikForumRequest;
use App\Models\ForumTopik;
use App\Models\PenugasanGuru;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Forum diskusi per kelas dan mapel; dipakai guru dan siswa (proses 5.x). */
class ForumTopikController extends Controller
{
    /** Pintu masuk forum: semua kelas dan mapel milik pengguna, dengan jumlah topik. */
    public function beranda(Request $request): View
    {
        $daftar = PenugasanGuru::query()
            ->diaksesOleh($request->user())
            ->with(['kelas', 'mataPelajaran', 'guru'])
            ->withCount('forumTopik')
            ->addSelect(['topik_terakhir' => ForumTopik::query()
                ->select('created_at')
                ->whereColumn('penugasan_guru_id', 'penugasan_guru.id')
                ->latest()
                ->limit(1)])
            ->get()
            ->sortBy(fn ($p) => $p->mataPelajaran->nama.$p->kelas->nama)
            ->values();

        return view('forum.topik.beranda', ['daftar' => $daftar]);
    }

    public function index(Request $request, PenugasanGuru $penugasan): View
    {
        $this->pastikanAkses($penugasan);

        $penugasan->load(['kelas', 'mataPelajaran', 'guru']);
        $kata = trim((string) $request->query('cari'));

        return view('forum.topik.index', [
            'penugasan' => $penugasan,
            'daftar' => $penugasan->forumTopik()
                ->with('pembuat')
                ->withCount('balasan')
                ->when($kata !== '', fn ($q) => $q->where(fn ($w) => $w
                    ->where('judul', 'like', "%{$kata}%")
                    ->orWhere('isi', 'like', "%{$kata}%")))
                ->urutTampil()
                ->paginate(10)
                ->withQueryString(),
        ]);
    }

    public function create(PenugasanGuru $penugasan): View
    {
        $this->authorize('create', [ForumTopik::class, $penugasan]);

        $penugasan->load(['kelas', 'mataPelajaran']);

        return view('forum.topik.create', ['penugasan' => $penugasan, 'topik' => new ForumTopik()]);
    }

    public function store(StoreTopikForumRequest $request, PenugasanGuru $penugasan): RedirectResponse
    {
        $this->authorize('create', [ForumTopik::class, $penugasan]);

        $topik = $penugasan->forumTopik()->create($request->validated() + ['user_id' => $request->user()->id]);

        return redirect()->route('forum.topik.show', $topik)->with('sukses', 'Topik berhasil dibuat.');
    }

    public function show(ForumTopik $topik): View
    {
        $this->authorize('view', $topik);

        $topik->load(['pembuat', 'penugasanGuru.kelas', 'penugasanGuru.mataPelajaran', 'penugasanGuru.guru']);

        $balasan = $topik->balasan()->with('pembuat')->oldest()->paginate(15);

        // Policy balasan membaca $balasan->topik; pakai topik yang sudah dimuat (hindari N+1).
        $balasan->getCollection()->each(fn ($b) => $b->setRelation('topik', $topik));

        return view('forum.topik.show', ['topik' => $topik, 'balasan' => $balasan]);
    }

    public function edit(ForumTopik $topik): View
    {
        $this->authorize('update', $topik);

        $topik->load(['penugasanGuru.kelas', 'penugasanGuru.mataPelajaran']);

        return view('forum.topik.edit', ['topik' => $topik, 'penugasan' => $topik->penugasanGuru]);
    }

    public function update(StoreTopikForumRequest $request, ForumTopik $topik): RedirectResponse
    {
        $this->authorize('update', $topik);

        $topik->update($request->validated());

        return redirect()->route('forum.topik.show', $topik)->with('sukses', 'Topik diperbarui.');
    }

    public function destroy(ForumTopik $topik): RedirectResponse
    {
        $this->authorize('delete', $topik);

        $penugasanId = $topik->penugasan_guru_id;
        $topik->delete(); // balasan ikut terhapus (cascade)

        return redirect()->route('forum.topik.index', $penugasanId)->with('sukses', 'Topik dihapus.');
    }
}
