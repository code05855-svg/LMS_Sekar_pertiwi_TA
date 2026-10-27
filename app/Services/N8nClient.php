<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Pembungkus HTTP tunggal untuk memanggil webhook n8n dari Laravel.
 * Rahasia dikirim sebagai header X-N8N-Secret (node Webhook n8n memeriksanya).
 */
class N8nClient
{
    /**
     * @param  array<string, mixed>  $data
     *
     * @throws \Illuminate\Http\Client\ConnectionException bila n8n tidak terjangkau
     */
    public function kirim(string $url, array $data, ?int $timeout = null): Response
    {
        return Http::timeout($timeout ?? (int) config('services.n8n.timeout'))
            ->connectTimeout(10)
            ->withHeaders(['X-N8N-Secret' => (string) config('services.n8n.secret')])
            ->acceptJson()
            ->post($url, $data);
    }
}
