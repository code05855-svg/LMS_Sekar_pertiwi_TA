<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MataPelajaran;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MataPelajaranController extends Controller
{
    public function index(Request $request): View
    {
        $kata = trim((string) $request->query('cari'));

        return view('admin.mata-pelajaran.index', [
            'daftar' => MataPelajaran::query()
                ->withCount('penugasanGuru')
                ->when($kata !== '', fn ($q) => $q->where(fn ($w) => $w
                    ->where('nama', 'like', "%{$kata}%")
                    ->orWhere('kode', 'like', "%{$kata}%")))
                ->orderBy('nama')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.mata-pelajaran.create', ['mapel' => new MataPelajaran()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $mapel = MataPelajaran::create($this->validasi($request));

        return redirect()->route('admin.mata-pelajaran.index')
            ->with('sukses', "Mata pelajaran {$mapel->nama} berhasil ditambahkan.");
    }

    public function edit(MataPelajaran $mapel): View
    {
        return view('admin.mata-pelajaran.edit', ['mapel' => $mapel]);
    }

    public function update(Request $request, MataPelajaran $mapel): RedirectResponse
    {
        $mapel->update($this->validasi($request, $mapel));

        return redirect()->route('admin.mata-pelajaran.index')
            ->with('sukses', "Mata pelajaran {$mapel->nama} berhasil diperbarui.");
    }

    public function destroy(MataPelajaran $mapel): RedirectResponse
    {
        try {
            $mapel->delete();
        } catch (QueryException) {
            return back()->with('galat', "Mata pelajaran {$mapel->nama} tidak dapat dihapus karena sudah dipakai pada penugasan guru.");
        }

        return redirect()->route('admin.mata-pelajaran.index')
            ->with('sukses', "Mata pelajaran {$mapel->nama} berhasil dihapus.");
    }

    /** @return array<string, mixed> */
    private function validasi(Request $request, ?MataPelajaran $mapel = null): array
    {
        $request->merge(['kode' => strtoupper($this->teks($request, 'kode'))]);

        return $request->validate([
            'kode' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9._-]+$/', Rule::unique('mata_pelajaran', 'kode')->ignore($mapel?->id)],
            'nama' => ['required', 'string', 'max:100'],
        ], [
            'kode.regex' => 'Kode hanya boleh berisi huruf, angka, titik, strip, dan garis bawah.',
        ]);
    }
}
