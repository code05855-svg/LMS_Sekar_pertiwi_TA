<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\FotoProfilService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Foto profil: semua pengguna (admin, guru, siswa) boleh mengganti fotonya sendiri. */
class FotoProfilController extends Controller
{
    public function store(Request $request, FotoProfilService $layanan): RedirectResponse
    {
        $request->validate([
            'foto' => [
                'bail', 'required', 'file',
                'mimes:jpg,jpeg,png,webp',
                'max:'.config('lms.unggah.foto_profil_kb'),
                Rule::dimensions()
                    ->minWidth(100)->minHeight(100)
                    ->maxWidth(config('lms.unggah.foto_profil_piksel_maks'))
                    ->maxHeight(config('lms.unggah.foto_profil_piksel_maks')),
                // Foto beresolusi sangat tinggi bisa menghabiskan memory_limit saat dibuka GD.
                function (string $atribut, mixed $berkas, \Closure $gagal) {
                    $info = $berkas instanceof UploadedFile ? @getimagesize($berkas->getRealPath()) : false;

                    if ($info && ! FotoProfilService::cukupMemori($info[0], $info[1])) {
                        $gagal("Resolusi foto terlalu tinggi untuk diproses server ({$info[0]} x {$info[1]} piksel). Perkecil fotonya lebih dulu (mis. lewat aplikasi galeri) lalu coba lagi.");
                    }
                },
            ],
        ], [
            'foto.required' => 'Pilih foto terlebih dahulu.',
            'foto.mimes' => 'Foto harus berformat JPG, PNG, atau WEBP.',
            'foto.max' => 'Ukuran foto maksimal '.round(config('lms.unggah.foto_profil_kb') / 1024).' MB.',
            'foto.dimensions' => 'Ukuran foto minimal 100 x 100 piksel dan maksimal '.config('lms.unggah.foto_profil_piksel_maks').' x '.config('lms.unggah.foto_profil_piksel_maks').' piksel.',
        ]);

        $layanan->simpan($request->user(), $request->file('foto'));

        return redirect()->route('profil.edit')->with('sukses', 'Foto profil diperbarui.');
    }

    public function destroy(Request $request, FotoProfilService $layanan): RedirectResponse
    {
        $layanan->hapus($request->user());

        return redirect()->route('profil.edit')->with('sukses', 'Foto profil dihapus. Inisial namamu dipakai kembali.');
    }

    /** Menyajikan foto; hanya untuk pengguna yang sudah login (route di grup auth). */
    public function show(User $user): StreamedResponse
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('foto_profil');

        abort_unless($user->foto_profil && $disk->exists($user->foto_profil), 404);

        return $disk->response($user->foto_profil, null, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=604800',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
