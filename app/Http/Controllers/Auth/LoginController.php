<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Login memakai nomor induk (NIS/NIP/kode admin). Email yang terdaftar
     * juga diterima sebagai alternatif.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nomor_induk' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
        ], [
            'nomor_induk.required' => 'Nomor induk wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $identitas = trim($data['nomor_induk']);

        $akun = User::query()
            ->where('nomor_induk', $identitas)
            ->when(str_contains($identitas, '@'), fn ($q) => $q->orWhere('email', $identitas))
            ->first();

        if ($akun && ! $akun->aktif && Hash::check($data['password'], $akun->password)) {
            throw ValidationException::withMessages([
                'nomor_induk' => 'Akun Anda dinonaktifkan. Hubungi admin sekolah.',
            ]);
        }

        $berhasil = $akun
            && Auth::attempt(
                ['nomor_induk' => $akun->nomor_induk, 'password' => $data['password'], 'aktif' => 1],
                $request->boolean('ingat'),
            );

        if (! $berhasil) {
            throw ValidationException::withMessages([
                'nomor_induk' => trans('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('sukses', 'Anda sudah keluar.');
    }
}
