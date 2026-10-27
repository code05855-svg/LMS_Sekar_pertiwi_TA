{{--
    Satu pasangan percakapan: pertanyaan pengguna, jawaban Sekar AI, dan sumber materi.
    Dikembalikan ChatbotController@kirim dan dipakai ulang untuk memuat riwayat.
--}}
@php($gagal = $riwayat->status === \App\Enums\StatusChat::Gagal)

<div class="space-y-3" id="chat-{{ $riwayat->id }}">
    <div class="gelembung-saya whitespace-pre-line break-words">{{ $riwayat->pertanyaan }}</div>

    <div @class(['gelembung-bot', '!bg-danger-soft' => $gagal])>
        <p @class(['whitespace-pre-line break-words', 'text-danger' => $gagal])>{{ $riwayat->jawaban }}</p>

        @if (! $gagal && ! empty($riwayat->sumber))
            @include('chatbot.partials.sumber', ['sumber' => $riwayat->sumber])
        @endif

        @if ($riwayat->waktu_respons_ms)
            <p class="mt-2 text-[11px] text-muted">{{ $gagal ? 'Gagal' : 'Dijawab' }} dalam {{ number_format($riwayat->waktu_respons_ms / 1000, 1, ',', '.') }} detik</p>
        @endif
    </div>
</div>
