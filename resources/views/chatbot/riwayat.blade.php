@extends('layouts.app')

@section('judul', 'Riwayat Chatbot')

@section('konten')
<x-judul-halaman judul="Riwayat pertanyaan" deskripsi="Semua pertanyaan yang pernah kamu ajukan ke Sekar AI, terbaru di atas." :kembali="route('chatbot.index')" label="Kembali ke chat" />

@if ($daftar->isEmpty())
    <x-card>
        <x-kosong judul="Belum ada pertanyaan" deskripsi="Pertanyaan dan jawaban Sekar AI akan tersimpan di sini." ikon="riwayat">
            <a href="{{ route('chatbot.index') }}" class="btn-primary btn-sm">Mulai bertanya</a>
        </x-kosong>
    </x-card>
@else
    <div class="space-y-4">
        @foreach ($daftar as $r)
            <section class="card p-5 sm:p-6">
                <p class="mb-4 flex items-center gap-2 text-xs text-muted">
                    <x-ikon nama="jam" class="size-3.5" /> {{ $r->created_at->translatedFormat('d M Y, H:i') }}
                    @if ($r->status === \App\Enums\StatusChat::Gagal)<x-badge-status :status="$r->status" />@endif
                </p>
                @include('chatbot.partials.pesan', ['riwayat' => $r])
            </section>
        @endforeach
    </div>

    <div class="mt-6">{{ $daftar->links() }}</div>
@endif
@endsection
