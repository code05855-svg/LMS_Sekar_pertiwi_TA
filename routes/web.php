<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ForumBalasanController;
use App\Http\Controllers\ForumTopikController;
use App\Http\Controllers\Guru;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\Siswa;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Route web
|--------------------------------------------------------------------------
|
| Nama route: peran.modul.aksi (mis. guru.materi.index). Halaman bersama
| (dashboard, profil, chatbot, forum) tidak diawali peran.
*/

// ---------------------------------------------------------------------
// Tamu
// ---------------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:10,1')->name('login.store');
});

Route::post('logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

// ---------------------------------------------------------------------
// Semua pengguna yang sudah login
// ---------------------------------------------------------------------
Route::middleware('auth')->group(function () {
    Route::redirect('/', '/dashboard');
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('profil', [ProfilController::class, 'edit'])->name('profil.edit');
    Route::put('profil', [ProfilController::class, 'update'])->name('profil.update');

    // Chatbot akademik: siswa dan guru (admin tidak punya materi untuk ditanyakan)
    Route::middleware('peran:siswa,guru')->prefix('chatbot')->name('chatbot.')->group(function () {
        Route::get('/', [ChatbotController::class, 'index'])->name('index');
        Route::post('kirim', [ChatbotController::class, 'kirim'])
            ->middleware('throttle:10,1')
            ->name('kirim');
        Route::post('baru', [ChatbotController::class, 'baru'])->name('baru');
        Route::get('riwayat', [ChatbotController::class, 'riwayat'])->name('riwayat');
    });

    // Forum diskusi: guru dan siswa, per penugasan (kelas + mapel)
    Route::middleware('peran:guru,siswa')->prefix('forum')->name('forum.')->group(function () {
        Route::get('/', [ForumTopikController::class, 'beranda'])->name('index');
        Route::get('penugasan/{penugasan}/topik', [ForumTopikController::class, 'index'])->name('topik.index');
        Route::get('penugasan/{penugasan}/topik/buat', [ForumTopikController::class, 'create'])->name('topik.create');
        Route::post('penugasan/{penugasan}/topik', [ForumTopikController::class, 'store'])->name('topik.store');
        Route::get('topik/{topik}', [ForumTopikController::class, 'show'])->name('topik.show');
        Route::get('topik/{topik}/ubah', [ForumTopikController::class, 'edit'])->name('topik.edit');
        Route::put('topik/{topik}', [ForumTopikController::class, 'update'])->name('topik.update');
        Route::delete('topik/{topik}', [ForumTopikController::class, 'destroy'])->name('topik.destroy');

        Route::post('topik/{topik}/balasan', [ForumBalasanController::class, 'store'])->name('balasan.store');
        Route::put('balasan/{balasan}', [ForumBalasanController::class, 'update'])->name('balasan.update');
        Route::delete('balasan/{balasan}', [ForumBalasanController::class, 'destroy'])->name('balasan.destroy');
    });
});

// ---------------------------------------------------------------------
// Admin: data dasar saja
// ---------------------------------------------------------------------
Route::middleware(['auth', 'peran:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('guru', Admin\GuruController::class)->except('show')->parameters(['guru' => 'akun']);
    Route::patch('guru/{akun}/status', [Admin\GuruController::class, 'status'])->name('guru.status');
    Route::patch('guru/{akun}/password', [Admin\GuruController::class, 'resetPassword'])->name('guru.password');

    Route::resource('siswa', Admin\SiswaController::class)->except('show')->parameters(['siswa' => 'akun']);
    Route::patch('siswa/{akun}/status', [Admin\SiswaController::class, 'status'])->name('siswa.status');
    Route::patch('siswa/{akun}/password', [Admin\SiswaController::class, 'resetPassword'])->name('siswa.password');

    Route::resource('kelas', Admin\KelasController::class)->parameters(['kelas' => 'kelas']);

    Route::resource('mata-pelajaran', Admin\MataPelajaranController::class)
        ->except('show')
        ->parameters(['mata-pelajaran' => 'mapel']);

    Route::resource('penugasan-guru', Admin\PenugasanGuruController::class)
        ->only(['index', 'create', 'store', 'destroy'])
        ->parameters(['penugasan-guru' => 'penugasan']);

    Route::resource('penempatan-siswa', Admin\PenempatanSiswaController::class)
        ->only(['index', 'store', 'destroy'])
        ->parameters(['penempatan-siswa' => 'siswa']);
});

// ---------------------------------------------------------------------
// Guru
// ---------------------------------------------------------------------
Route::middleware(['auth', 'peran:guru'])->prefix('guru')->name('guru.')->group(function () {
    // "kelas" bagi guru = satu penugasan (mapel + kelas yang diampu)
    Route::resource('kelas', Guru\KelasController::class)->only(['index', 'show'])->parameters(['kelas' => 'penugasan']);

    Route::resource('materi', Guru\MateriController::class)->parameters(['materi' => 'materi']);
    Route::post('materi/{materi}/berkas', [Guru\BerkasMateriController::class, 'store'])->name('berkas.store');
    Route::get('berkas/{berkas}', [Guru\BerkasMateriController::class, 'show'])->name('berkas.show');
    Route::put('berkas/{berkas}', [Guru\BerkasMateriController::class, 'update'])->name('berkas.update');
    Route::delete('berkas/{berkas}', [Guru\BerkasMateriController::class, 'destroy'])->name('berkas.destroy');
    Route::post('berkas/{berkas}/reindeks', [Guru\BerkasMateriController::class, 'reindeks'])->name('berkas.reindeks');

    Route::resource('tugas', Guru\TugasController::class)->parameters(['tugas' => 'tugas']);

    Route::get('tugas/{tugas}/pengumpulan', [Guru\PengumpulanTugasController::class, 'index'])->name('pengumpulan-tugas.index');
    Route::get('pengumpulan-tugas/{pengumpulan}', [Guru\PengumpulanTugasController::class, 'show'])->name('pengumpulan-tugas.show');
    Route::get('pengumpulan-tugas/{pengumpulan}/unduh', [Guru\PengumpulanTugasController::class, 'unduh'])->name('pengumpulan-tugas.unduh');
    Route::put('pengumpulan-tugas/{pengumpulan}/nilai', [Guru\PenilaianController::class, 'update'])->name('penilaian.update');

    Route::patch('forum/topik/{topik}/sematkan', [Guru\ForumModerasiController::class, 'sematkan'])->name('forum.sematkan');
    Route::patch('forum/topik/{topik}/kunci', [Guru\ForumModerasiController::class, 'kunci'])->name('forum.kunci');
});

// ---------------------------------------------------------------------
// Siswa
// ---------------------------------------------------------------------
Route::middleware(['auth', 'peran:siswa'])->prefix('siswa')->name('siswa.')->group(function () {
    Route::resource('mata-pelajaran', Siswa\MataPelajaranController::class)
        ->only(['index', 'show'])
        ->parameters(['mata-pelajaran' => 'penugasan']);

    Route::get('materi/{materi}', [Siswa\MateriController::class, 'show'])->name('materi.show');
    Route::get('berkas/{berkas}/unduh', [Siswa\MateriController::class, 'unduh'])->name('materi.unduh');

    Route::get('tugas/{tugas}', [Siswa\TugasController::class, 'show'])->name('tugas.show');
    Route::post('tugas/{tugas}/pengumpulan', [Siswa\PengumpulanTugasController::class, 'store'])->name('pengumpulan-tugas.store');
    Route::put('pengumpulan-tugas/{pengumpulan}', [Siswa\PengumpulanTugasController::class, 'update'])->name('pengumpulan-tugas.update');
    Route::delete('pengumpulan-tugas/{pengumpulan}', [Siswa\PengumpulanTugasController::class, 'destroy'])->name('pengumpulan-tugas.destroy');
    Route::get('pengumpulan-tugas/{pengumpulan}/unduh', [Siswa\PengumpulanTugasController::class, 'unduh'])->name('pengumpulan-tugas.unduh');

    Route::get('nilai', [Siswa\NilaiController::class, 'index'])->name('nilai.index');
});
