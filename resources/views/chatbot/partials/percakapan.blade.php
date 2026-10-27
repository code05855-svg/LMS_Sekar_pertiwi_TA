{{-- Daftar percakapan (pasangan pertanyaan + jawaban). Dipakai kotak chat dan pemuatan widget. --}}
@foreach ($percakapan as $riwayat)
    @include('chatbot.partials.pesan', ['riwayat' => $riwayat])
@endforeach
