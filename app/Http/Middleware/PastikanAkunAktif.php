<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Akun yang dinonaktifkan admin langsung dikeluarkan pada permintaan
 * berikutnya, walaupun sesi login-nya masih berlaku.
 */
class PastikanAkunAktif
{
    public function handle(Request $request, Closure $next): Response
    {
        $pengguna = $request->user();

        if ($pengguna && ! $pengguna->aktif) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['nomor_induk' => 'Akun Anda dinonaktifkan. Hubungi admin sekolah.']);
        }

        return $next($request);
    }
}
