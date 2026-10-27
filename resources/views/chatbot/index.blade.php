@extends('layouts.app')

@section('judul', 'Sekar AI')

@section('konten')
<x-judul-halaman judul="Sekar AI" deskripsi="Asisten akademik yang menjawab dari materi PDF di kelasmu.">
    <a href="{{ route('chatbot.riwayat') }}" class="btn-secondary"><x-ikon nama="riwayat" class="size-4" /> Riwayat</a>
    <form method="POST" action="{{ route('chatbot.baru') }}">
        @csrf
        <button class="btn-primary" data-tetap><x-ikon nama="tambah" class="size-4" /> Percakapan baru</button>
    </form>
</x-judul-halaman>

<div class="grid gap-5 lg:grid-cols-3">
    <section class="card flex h-[calc(100dvh-14rem)] min-h-[28rem] flex-col overflow-hidden lg:col-span-2">
        <header class="flex items-center gap-3 border-b border-line px-5 py-3.5">
            <span class="grid size-10 place-items-center rounded-full bg-navy text-gold"><x-ikon nama="robot" class="size-5" /></span>
            <div>
                <p class="font-bold text-ink">Sekar AI</p>
                <p class="flex items-center gap-1.5 text-xs text-muted"><span class="size-1.5 rounded-full bg-success"></span> Siap menjawab dari materi terbit</p>
            </div>
        </header>
        <x-chatbot-kotak id="halaman" class="flex-1" :percakapan="$percakapan" :saran="$saran" :muat-awal="true" />
    </section>

    <aside class="space-y-5">
        <x-card judul="Cara bertanya yang baik">
            <ul class="space-y-3 text-sm text-muted">
                <li class="flex gap-3"><x-ikon nama="centang" class="mt-0.5 size-4 shrink-0 text-success" /><span>Sebut topiknya, mis. <em class="text-ink">"Apa fungsi klorofil pada fotosintesis?"</em></span></li>
                <li class="flex gap-3"><x-ikon nama="centang" class="mt-0.5 size-4 shrink-0 text-success" /><span>Satu pertanyaan sekali kirim, supaya jawabannya fokus.</span></li>
                <li class="flex gap-3"><x-ikon nama="centang" class="mt-0.5 size-4 shrink-0 text-success" /><span>Cek bagian <strong class="text-ink">Sumber</strong> di bawah jawaban untuk membuka materi aslinya.</span></li>
            </ul>
        </x-card>

        <x-card judul="Perlu diingat">
            <ul class="space-y-3 text-sm text-muted">
                <li class="flex gap-3"><x-ikon nama="info" class="mt-0.5 size-4 shrink-0 text-navy" /><span>Sekar AI hanya memakai materi <strong class="text-ink">yang sudah terbit</strong> di kelasmu.</span></li>
                <li class="flex gap-3"><x-ikon nama="info" class="mt-0.5 size-4 shrink-0 text-navy" /><span>Jika materinya belum ada, Sekar AI akan bilang tidak menemukan jawaban, bukan mengarang.</span></li>
                <li class="flex gap-3"><x-ikon nama="peringatan" class="mt-0.5 size-4 shrink-0 text-gold-600" /><span>AI bisa keliru. Konfirmasikan hal penting kepada gurumu.</span></li>
            </ul>
        </x-card>
    </aside>
</div>
@endsection
