<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PeranPengguna;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAkunRequest;
use App\Http\Requests\UpdateAkunRequest;
use App\Models\Kelas;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dasar bersama GuruController dan SiswaController (kelola akun, proses 1.x).
 * Akun tidak dihapus bila sudah punya data (foreign key RESTRICT); admin
 * diarahkan untuk menonaktifkannya.
 */
abstract class AkunController extends Controller
{
    abstract protected function peran(): PeranPengguna;

    /** Folder view dan awalan route: "guru" atau "siswa". */
    abstract protected function folder(): string;

    abstract protected function labelAkun(): string;

    protected function pastikanPeran(User $akun): User
    {
        abort_unless($akun->peran === $this->peran(), 404);

        return $akun;
    }

    public function index(Request $request): View
    {
        $status = $request->query('status');

        $daftar = User::query()
            ->berperan($this->peran())
            ->cari($request->query('cari'))
            ->when($status === 'aktif', fn ($q) => $q->where('aktif', true))
            ->when($status === 'nonaktif', fn ($q) => $q->where('aktif', false))
            ->when($this->peran() === PeranPengguna::Siswa, function ($q) use ($request) {
                $q->with('kelas');
                $kelas = $request->query('kelas');
                if ($kelas === 'tanpa') {
                    $q->whereNull('kelas_id');
                } elseif (filled($kelas)) {
                    $q->where('kelas_id', $kelas);
                }
            })
            ->when($this->peran() === PeranPengguna::Guru, fn ($q) => $q->withCount('penugasanGuru'))
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString();

        return view("admin.{$this->folder()}.index", [
            'daftar' => $daftar,
            'daftarKelas' => Kelas::urut()->get(),
            'ringkasan' => [
                'total' => User::berperan($this->peran())->count(),
                'aktif' => User::berperan($this->peran())->aktif()->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view("admin.{$this->folder()}.create", [
            'akun' => new User(['aktif' => true]),
            'daftarKelas' => Kelas::urut()->get(),
        ]);
    }

    public function store(StoreAkunRequest $request): RedirectResponse
    {
        $akun = User::create($request->dataAkun());

        return redirect()->route("admin.{$this->folder()}.index")
            ->with('sukses', "Akun {$this->labelAkun()} {$akun->nama} berhasil ditambahkan.");
    }

    public function edit(User $akun): View
    {
        return view("admin.{$this->folder()}.edit", [
            'akun' => $this->pastikanPeran($akun),
            'daftarKelas' => Kelas::urut()->get(),
        ]);
    }

    public function update(UpdateAkunRequest $request, User $akun): RedirectResponse
    {
        $this->pastikanPeran($akun)->update($request->dataAkun());

        return redirect()->route("admin.{$this->folder()}.index")
            ->with('sukses', "Akun {$akun->nama} berhasil diperbarui.");
    }

    public function destroy(User $akun): RedirectResponse
    {
        $this->pastikanPeran($akun);

        try {
            $akun->delete();
        } catch (QueryException) {
            return back()->with('galat', "Akun {$akun->nama} tidak dapat dihapus karena sudah punya data "
                .'(penugasan, tugas, atau forum). Nonaktifkan akun ini sebagai gantinya.');
        }

        return redirect()->route("admin.{$this->folder()}.index")
            ->with('sukses', "Akun {$akun->nama} berhasil dihapus.");
    }

    /** Aktifkan atau nonaktifkan akun (akun nonaktif tidak bisa login). */
    public function status(User $akun): RedirectResponse
    {
        $this->pastikanPeran($akun)->update(['aktif' => ! $akun->aktif]);

        return back()->with('sukses', $akun->aktif
            ? "Akun {$akun->nama} diaktifkan."
            : "Akun {$akun->nama} dinonaktifkan.");
    }

    /** Admin mengatur ulang password (pengguna yang lupa password). */
    public function resetPassword(Request $request, User $akun): RedirectResponse
    {
        $data = $request->validate(
            ['password' => ['required', 'string', 'min:8', 'max:100']],
            [],
            ['password' => 'password baru'],
        );

        $this->pastikanPeran($akun)->update(['password' => $data['password']]);

        return back()->with('sukses', "Password {$akun->nama} berhasil diatur ulang.");
    }
}
