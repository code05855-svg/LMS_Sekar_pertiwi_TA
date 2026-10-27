<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TingkatKelas;
use App\Http\Controllers\Controller;
use App\Models\Kelas;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KelasController extends Controller
{
    public function index(Request $request): View
    {
        $tahun = $request->query('tahun_ajaran');

        return view('admin.kelas.index', [
            'daftar' => Kelas::query()
                ->withCount(['siswa', 'penugasanGuru'])
                ->when(filled($tahun), fn ($q) => $q->tahunAjaran($tahun))
                ->urut()
                ->paginate(12)
                ->withQueryString(),
            'daftarTahun' => Kelas::query()->select('tahun_ajaran')->distinct()->orderByDesc('tahun_ajaran')->pluck('tahun_ajaran'),
        ]);
    }

    public function create(): View
    {
        return view('admin.kelas.create', ['kelas' => new Kelas(['tahun_ajaran' => $this->tahunAjaranBerjalan()])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $kelas = Kelas::create($this->validasi($request));

        return redirect()->route('admin.kelas.show', $kelas)
            ->with('sukses', "Kelas {$kelas->tingkat->value} {$kelas->nama} berhasil ditambahkan.");
    }

    /** Guru pengampu dan siswa di kelas. */
    public function show(Kelas $kelas): View
    {
        $kelas->load([
            'penugasanGuru.guru',
            'penugasanGuru.mataPelajaran',
            'siswa' => fn ($q) => $q->orderBy('nama'),
        ]);

        return view('admin.kelas.show', ['kelas' => $kelas]);
    }

    public function edit(Kelas $kelas): View
    {
        return view('admin.kelas.edit', ['kelas' => $kelas]);
    }

    public function update(Request $request, Kelas $kelas): RedirectResponse
    {
        $kelas->update($this->validasi($request, $kelas));

        return redirect()->route('admin.kelas.show', $kelas)
            ->with('sukses', 'Data kelas berhasil diperbarui.');
    }

    public function destroy(Kelas $kelas): RedirectResponse
    {
        try {
            $kelas->delete(); // siswa di kelas ini otomatis menjadi "belum ditempatkan" (SET NULL)
        } catch (QueryException) {
            return back()->with('galat', 'Kelas tidak dapat dihapus karena masih punya penugasan guru. '
                .'Hapus penugasan guru pada kelas ini terlebih dahulu.');
        }

        return redirect()->route('admin.kelas.index')->with('sukses', 'Kelas berhasil dihapus.');
    }

    /** @return array<string, mixed> */
    private function validasi(Request $request, ?Kelas $kelas = null): array
    {
        return $request->validate([
            'tingkat' => ['required', Rule::enum(TingkatKelas::class)],
            'nama' => [
                'required', 'string', 'max:30',
                Rule::unique('kelas', 'nama')
                    ->where('tahun_ajaran', $this->teks($request, 'tahun_ajaran'))
                    ->ignore($kelas?->id),
            ],
            'tahun_ajaran' => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/'],
        ], [
            'nama.unique' => 'Kelas dengan nama dan tahun ajaran ini sudah ada.',
            'tahun_ajaran.regex' => 'Format tahun ajaran harus seperti 2026/2027.',
        ]);
    }

    /** Juli sampai Desember = tahun ini/tahun depan; Januari sampai Juni = tahun lalu/tahun ini. */
    private function tahunAjaranBerjalan(): string
    {
        $mulai = now()->month >= 7 ? now()->year : now()->year - 1;

        return $mulai.'/'.($mulai + 1);
    }
}
