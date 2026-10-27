<?php

namespace App\Http\Middleware;

use App\Enums\PeranPengguna;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi route per peran. Dipakai lewat alias "peran":
 *
 *   Route::middleware(['auth', 'peran:guru'])
 *   Route::middleware(['auth', 'peran:guru,siswa'])
 *
 * Admin hanya boleh membuka halaman admin; halaman guru tidak terbuka
 * untuk admin (sesuai Kerangka bagian 2, "Akses Admin").
 */
class EnsurePeran
{
    public function handle(Request $request, Closure $next, string ...$peran): Response
    {
        $pengguna = $request->user();

        if (! $pengguna) {
            return redirect()->guest(route('login'));
        }

        $diizinkan = array_map(PeranPengguna::from(...), $peran);

        abort_unless(
            $pengguna->punyaPeran(...$diizinkan),
            403,
            'Halaman ini tidak tersedia untuk peran Anda.'
        );

        return $next($request);
    }
}
