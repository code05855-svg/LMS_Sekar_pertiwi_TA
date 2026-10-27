<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** Profil: lihat data akun dan ganti password (hanya password yang dapat diubah). */
class ProfilController extends Controller
{
    public function edit(Request $request): View
    {
        $pengguna = $request->user()->load('kelas');

        return view('profil.edit', ['pengguna' => $pengguna]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'password_saat_ini' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers(), 'different:password_saat_ini'],
        ], [
            'password_saat_ini.current_password' => 'Password saat ini salah.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'password.different' => 'Password baru harus berbeda dari password saat ini.',
        ]);

        $request->user()->update(['password' => $data['password']]);

        return redirect()->route('profil.edit')->with('sukses', 'Password berhasil diganti.');
    }
}
