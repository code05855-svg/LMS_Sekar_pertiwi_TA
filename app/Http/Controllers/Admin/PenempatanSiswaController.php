<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Penempatan siswa ke kelas lewat users.kelas_id (satu siswa satu kelas). */
class PenempatanSiswaController extends Controller
{
    public function index(Request $request): View
    {
        $kelasDipilih = $request->filled('kelas') ? Kelas::find($request->query('kelas')) : null;

        return view('admin.penempatan-siswa.index', [
            'daftarKelas' => Kelas::query()->withCount('siswa')->urut()->get(),
            'kelasDipilih' => $kelasDipilih,
            'siswaDiKelas' => $kelasDipilih
                ? User::siswa()->where('kelas_id', $kelasDipilih->id)->orderBy('nama')->get()
                : collect(),
            'siswaBelumPunyaKelas' => User::siswa()->aktif()->whereNull('kelas_id')->orderBy('nama')->get(),
        ]);
    }

    /** Tempatkan beberapa siswa sekaligus ke satu kelas (juga memindahkan antar kelas). */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kelas_id' => ['required', 'integer', Rule::exists('kelas', 'id')],
            'siswa_ids' => ['required', 'array', 'min:1'],
            'siswa_ids.*' => ['integer', Rule::exists('users', 'id')->where('peran', 'siswa')],
        ], [
            'siswa_ids.required' => 'Pilih minimal satu siswa.',
            'siswa_ids.min' => 'Pilih minimal satu siswa.',
        ]);

        $jumlah = User::siswa()->whereIn('id', $data['siswa_ids'])->update(['kelas_id' => $data['kelas_id']]);

        return redirect()->route('admin.penempatan-siswa.index', ['kelas' => $data['kelas_id']])
            ->with('sukses', "{$jumlah} siswa berhasil ditempatkan.");
    }

    /** Keluarkan siswa dari kelas (kelas_id menjadi kosong). */
    public function destroy(User $siswa): RedirectResponse
    {
        abort_unless($siswa->adalahSiswa(), 404);

        $kelasId = $siswa->kelas_id;
        $siswa->update(['kelas_id' => null]);

        return redirect()->route('admin.penempatan-siswa.index', ['kelas' => $kelasId])
            ->with('sukses', "{$siswa->nama} dikeluarkan dari kelas.");
    }
}
