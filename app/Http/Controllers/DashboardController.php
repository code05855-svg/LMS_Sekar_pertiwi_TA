<?php

namespace App\Http\Controllers;

use App\Enums\StatusChat;
use App\Enums\StatusIndeks;
use App\Models\BerkasMateri;
use App\Models\ForumTopik;
use App\Models\Kelas;
use App\Models\Materi;
use App\Models\MataPelajaran;
use App\Models\PengumpulanTugas;
use App\Models\PenugasanGuru;
use App\Models\RiwayatChat;
use App\Models\Tugas;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Beranda sesuai peran (bento: kartu ringkasan dan daftar). */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return match (true) {
            $user->adalahAdmin() => view('dashboard.admin', $this->dataAdmin()),
            $user->adalahGuru() => view('dashboard.guru', $this->dataGuru($user)),
            default => view('dashboard.siswa', $this->dataSiswa($user)),
        };
    }

    /** @return array<string, mixed> */
    private function dataAdmin(): array
    {
        $indeks = BerkasMateri::query()
            ->selectRaw('status_indeks, count(*) as jumlah')
            ->groupBy('status_indeks')
            ->pluck('jumlah', 'status_indeks');

        return [
            'statistik' => [
                'guru' => User::guru()->aktif()->count(),
                'siswa' => User::siswa()->aktif()->count(),
                'kelas' => Kelas::count(),
                'mapel' => MataPelajaran::count(),
                'penugasan' => PenugasanGuru::count(),
            ],
            'siswaTanpaKelas' => User::siswa()->aktif()->whereNull('kelas_id')->count(),
            'kelasTanpaGuru' => Kelas::query()->whereDoesntHave('penugasanGuru')->count(),
            'daftarKelas' => Kelas::query()->withCount(['siswa', 'penugasanGuru'])->urut()->limit(8)->get(),
            'akunTerbaru' => User::query()->where('peran', '!=', 'admin')->latest()->limit(6)->get(),
            'chatbot' => [
                'terindeks' => (int) ($indeks[StatusIndeks::Terindeks->value] ?? 0),
                'proses' => (int) (($indeks[StatusIndeks::Menunggu->value] ?? 0) + ($indeks[StatusIndeks::Diproses->value] ?? 0)),
                'gagal' => (int) ($indeks[StatusIndeks::Gagal->value] ?? 0),
                'pertanyaan' => RiwayatChat::count(),
                'pertanyaan_gagal' => RiwayatChat::where('status', StatusChat::Gagal)->count(),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function dataGuru(User $guru): array
    {
        $penugasan = PenugasanGuru::query()
            ->diaksesOleh($guru)
            ->with(['kelas' => fn ($q) => $q->withCount('siswa'), 'mataPelajaran'])
            ->withCount(['materi', 'tugas'])
            ->get()
            ->sortBy(fn ($p) => $p->kelas->nama.$p->mataPelajaran->nama)
            ->values();

        $perluDinilai = PengumpulanTugas::query()
            ->belumDinilai()
            ->whereHas('tugas', fn ($q) => $q->diaksesOleh($guru))
            ->with(['siswa', 'tugas.penugasanGuru.kelas', 'tugas.penugasanGuru.mataPelajaran']);

        return [
            'daftarPenugasan' => $penugasan,
            'statistik' => [
                'kelas' => $penugasan->count(),
                'siswa' => $penugasan->unique('kelas_id')->sum(fn ($p) => $p->kelas->siswa_count),
                'materi_terbit' => Materi::query()->untukGuru($guru)->terbit()->count(),
            ],
            'jumlahPerluDinilai' => (clone $perluDinilai)->count(),
            'perluDinilai' => (clone $perluDinilai)->oldest('waktu_kumpul')->limit(6)->get(),
            'tenggatTerdekat' => Tugas::query()
                ->diaksesOleh($guru)
                ->berjalan()
                ->with(['penugasanGuru.kelas', 'penugasanGuru.mataPelajaran'])
                ->withCount('pengumpulanTugas')
                ->urutTenggat()
                ->limit(4)
                ->get(),
            'materiTerbaru' => Materi::query()
                ->untukGuru($guru)
                ->with(['penugasanGuru.kelas', 'penugasanGuru.mataPelajaran'])
                ->withCount('berkasMateri')
                ->latest()
                ->limit(5)
                ->get(),
            'berkasGagal' => BerkasMateri::query()
                ->where('status_indeks', StatusIndeks::Gagal)
                ->whereHas('materi', fn ($q) => $q->untukGuru($guru))
                ->with('materi')
                ->get(),
            'topikTerbaru' => ForumTopik::query()
                ->diaksesOleh($guru)
                ->with(['pembuat', 'penugasanGuru.kelas', 'penugasanGuru.mataPelajaran'])
                ->withCount('balasan')
                ->latest()
                ->limit(4)
                ->get(),
        ];
    }

    /** @return array<string, mixed> */
    private function dataSiswa(User $siswa): array
    {
        $siswa->load('kelas');

        $penugasan = PenugasanGuru::query()
            ->diaksesOleh($siswa)
            ->with(['guru', 'mataPelajaran'])
            ->withCount(['materi' => fn ($q) => $q->terbit(), 'tugas'])
            ->get()
            ->sortBy(fn ($p) => $p->mataPelajaran->nama)
            ->values();

        $belumDikumpulkan = fn ($q) => $q->whereDoesntHave(
            'pengumpulanTugas',
            fn ($p) => $p->where('siswa_id', $siswa->id)
        );

        return [
            'daftarPenugasan' => $penugasan,
            'tugasMendatang' => Tugas::query()
                ->diaksesOleh($siswa)
                ->berjalan()
                ->where($belumDikumpulkan)
                ->with(['penugasanGuru.mataPelajaran'])
                ->urutTenggat()
                ->limit(5)
                ->get(),
            'tugasLewat' => Tugas::query()
                ->diaksesOleh($siswa)
                ->where('tenggat', '<', now())
                ->where($belumDikumpulkan)
                ->with(['penugasanGuru.mataPelajaran'])
                ->orderByDesc('tenggat')
                ->limit(3)
                ->get(),
            'nilaiTerbaru' => PengumpulanTugas::query()
                ->milikSiswa($siswa)
                ->sudahDinilai()
                ->with(['tugas.penugasanGuru.mataPelajaran'])
                ->latest('dinilai_pada')
                ->limit(4)
                ->get(),
            'materiTerbaru' => Materi::query()
                ->terbit()
                ->untukSiswa($siswa)
                ->with(['penugasanGuru.mataPelajaran'])
                ->latest()
                ->limit(5)
                ->get(),
            'topikTerbaru' => ForumTopik::query()
                ->diaksesOleh($siswa)
                ->with(['pembuat', 'penugasanGuru.mataPelajaran'])
                ->withCount('balasan')
                ->latest()
                ->limit(4)
                ->get(),
        ];
    }
}
