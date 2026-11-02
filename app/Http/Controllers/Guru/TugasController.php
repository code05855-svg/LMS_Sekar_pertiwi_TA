<?php
 
namespace App\Http\Controllers\Guru;
 
use App\Enums\JenisJawaban;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTugasRequest;
use App\Models\PenugasanGuru;
use App\Models\Tugas;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
                'jenis_jawaban' => JenisJawaban::nilaiSemua(),
            ]),
            'daftarPenugasan' => $this->penugasanGuru($request->user()),
        ]);
    }
 
    public function store(StoreTugasRequest $request): RedirectResponse
    {
        $this->authorize('create', Tugas::class);
        $this->pastikanBatasLampiran($request);
 
        $tugas = Tugas::create($request->safe()->only([
            'penugasan_guru_id', 'judul', 'instruksi', 'tenggat', 'nilai_maksimum', 'jenis_jawaban',
        ]));
 
        $this->simpanLampiran($request, $tugas);
 
        return redirect()->route('guru.tugas.show', $tugas)->with('sukses', 'Tugas berhasil dibuat.');
    }
 
    public function show(Tugas $tugas): View
    {
        $this->authorize('view', $tugas);
 
        $tugas->load(['penugasanGuru.kelas', 'penugasanGuru.mataPelajaran', 'lampiran']);
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
 
        $tugas->load(['penugasanGuru.kelas', 'penugasanGuru.mataPelajaran', 'lampiran']);
 
        return view('guru.tugas.edit', ['tugas' => $tugas]);
    }
 
    public function update(StoreTugasRequest $request, Tugas $tugas): RedirectResponse
    {
        $this->authorize('update', $tugas);
        $this->pastikanBatasLampiran($request, $tugas);
 
        $tugas->update($request->safe()->only(['judul', 'instruksi', 'tenggat', 'nilai_maksimum', 'jenis_jawaban']));
 
        $this->hapusLampiran($request, $tugas);
        $this->simpanLampiran($request, $tugas);
 
        return redirect()->route('guru.tugas.show', $tugas)->with('sukses', 'Tugas berhasil diperbarui.');
    }
 
    public function destroy(Tugas $tugas): RedirectResponse
    {
        $this->authorize('delete', $tugas);
 
        $penugasanId = $tugas->penugasan_guru_id;
 
        DB::transaction(function () use ($tugas) {
            // Berkas jawaban siswa dan lampiran guru ikut dihapus dari penyimpanan.
            $tugas->pengumpulanTugas()->whereNotNull('path')->pluck('path')->each(fn ($path) => Storage::disk('pengumpulan')->delete($path));
            $tugas->lampiran()->whereNotNull('path')->pluck('path')->each(fn ($path) => Storage::disk('lampiran_tugas')->delete($path));
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
 
    /** Hapus lampiran yang dicentang pada form ubah (hanya milik tugas ini). */
    private function hapusLampiran(StoreTugasRequest $request, Tugas $tugas): void
    {
        $ids = array_map('intval', (array) $request->validated('hapus_lampiran', []));
 
        if ($ids === []) {
            return;
        }
 
        $tugas->lampiran()->whereIn('id', $ids)->get()->each(function ($lampiran) {
            if ($lampiran->path) {
                Storage::disk('lampiran_tugas')->delete($lampiran->path);
            }
 
            $lampiran->delete();
        });
    }
 
    /** Total lampiran per tugas (yang sudah ada dikurangi yang dihapus, ditambah yang baru) dibatasi. */
    private function pastikanBatasLampiran(StoreTugasRequest $request, ?Tugas $tugas = null): void
    {
        $maks = (int) config('lms.unggah.lampiran_maks');
        $hapus = array_map('intval', (array) $request->validated('hapus_lampiran', []));
        $ada = $tugas ? $tugas->lampiran()->whereNotIn('id', $hapus)->count() : 0;
        $baru = count($request->file('lampiran_berkas', [])) + count($request->validated('lampiran_tautan', []));
 
        if ($ada + $baru > $maks) {
            throw ValidationException::withMessages([
                'lampiran_berkas' => "Satu tugas maksimal memiliki {$maks} lampiran (berkas dan tautan). Hapus lampiran lama atau kurangi yang baru.",
            ]);
        }
    }
 
    /** Simpan lampiran baru (berkas dan tautan). */
    private function simpanLampiran(StoreTugasRequest $request, Tugas $tugas): void
    {
        $berkas = $request->file('lampiran_berkas', []);
        $tautan = $request->validated('lampiran_tautan', []);
 
        foreach ($berkas as $file) {
            $ekstensi = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
            $foto = in_array($ekstensi, config('lms.unggah.ekstensi_foto'), true);
 
            $tugas->lampiran()->create([
                'jenis' => $foto ? JenisJawaban::Foto : JenisJawaban::Dokumen,
                'nama_asli' => Str::limit($file->getClientOriginalName(), 250, ''),
                'path' => Storage::disk('lampiran_tugas')->putFileAs((string) $tugas->id, $file, Str::uuid().'.'.$ekstensi),
                'mime' => $file->getMimeType() ?: 'application/octet-stream',
                'ukuran' => $file->getSize(),
            ]);
        }
 
        foreach ($tautan as $url) {
            $tugas->lampiran()->create(['jenis' => JenisJawaban::Tautan, 'tautan' => $url]);
        }
    }
}
