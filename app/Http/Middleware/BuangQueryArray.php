<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Parameter query (?cari=...) di LMS selalu teks. Bentuk array seperti ?cari[]=x
 * dibuang di sini supaya filter dan pencarian tidak menjadi galat 500.
 */
class BuangQueryArray
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->query->replace(array_filter(
            $request->query->all(),
            static fn ($nilai) => is_scalar($nilai) || $nilai === null,
        ));

        return $next($request);
    }
}
