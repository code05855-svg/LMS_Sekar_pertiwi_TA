@extends('layouts.app')

@section('judul', $materi->judul)

@section('konten')
@php($pg = $materi->penugasanGuru)

<x-judul-halaman :judul="$materi->judul" :deskripsi="$pg->mataPelajaran->nama.' · Kelas '.$pg->kelas->nama.' · dibuat '.$materi->created_at->translatedFormat('d M Y')" :kembali="route('guru.kelas.show', $pg)" label="{{ $pg->mataPelajaran->nama }} {{ $pg->kelas->nama }}">
    <a href="{{ route('guru.materi.edit', $materi) }}" class="btn-secondary"><x-ikon nama="ubah" class="size-4" /> Ubah</a>
    <form method="POST" action="{{ route('guru.materi.destroy', $materi) }}" data-konfirmasi="Materi “{{ $materi->judul }}” beserta semua PDF dan indeks chatbot-nya akan dihapus permanen." data-judul="Hapus materi?" data-tombol="Hapus">
        @csrf @method('DELETE')
        <button type="submit" class="btn-danger-soft"><x-ikon nama="hapus" class="size-4" /> Hapus</button>
    </form>
</x-judul-halaman>

<div class="grid gap-5 lg:grid-cols-3">
    <div class="space-y-5 lg:col-span-2">
        <x-card judul="Berkas PDF ({{ $materi->berkasMateri->count() }})" :tanpa-padding="true">
            <x-slot:aksi>
                <button type="button" class="btn-primary btn-sm" @click="$dispatch('buka-modal', 'tambah-berkas')"><x-ikon nama="unggah" class="size-4" /> Tambah PDF</button>
            </x-slot:aksi>

            @if ($materi->berkasMateri->isEmpty())
                <x-kosong judul="Belum ada PDF" deskripsi="Unggah PDF supaya siswa dapat membacanya dan chatbot dapat menjawab dari isinya." ikon="unggah">
                    <button type="button" class="btn-primary btn-sm" @click="$dispatch('buka-modal', 'tambah-berkas')">Tambah PDF</button>
                </x-kosong>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($materi->berkasMateri as $berkas)
                        @include('guru.materi.partials.berkas', ['berkas' => $berkas])
                    @endforeach
                </ul>
            @endif
        </x-card>

        <x-card judul="Tentang materi">
            @if (filled($materi->deskripsi))
                <p class="whitespace-pre-line text-sm leading-relaxed text-ink">{{ $materi->deskripsi }}</p>
            @else
                <p class="text-sm text-muted">Belum ada deskripsi. <a href="{{ route('guru.materi.edit', $materi) }}" class="tautan">Tambahkan</a>.</p>
            @endif
        </x-card>
    </div>

    <div class="space-y-5">
        <x-card judul="Status">
            <div class="flex items-center justify-between">
                <x-badge-status :status="$materi->status" />
                <span class="text-xs text-muted">Diubah {{ $materi->updated_at->diffForHumans() }}</span>
            </div>
            @if ($materi->adalahTerbit())
                <p class="mt-3 text-sm text-muted">Siswa Kelas {{ $pg->kelas->nama }} dapat melihat materi ini, dan chatbot memakai PDF yang sudah terindeks.</p>
            @else
                <p class="mt-3 text-sm text-muted">Masih draf: siswa belum melihatnya dan chatbot belum memakainya. Ubah status menjadi Terbit bila sudah siap.</p>
                <a href="{{ route('guru.materi.edit', $materi) }}" class="btn-gold btn-sm mt-4">Terbitkan</a>
            @endif
        </x-card>

        <x-card judul="Cara kerja chatbot">
            <ol class="space-y-3 text-sm text-muted">
                <li class="flex gap-3"><span class="grid size-6 shrink-0 place-items-center rounded-full bg-info-soft text-xs font-bold text-navy">1</span> PDF diunggah, n8n membaca teksnya per halaman.</li>
                <li class="flex gap-3"><span class="grid size-6 shrink-0 place-items-center rounded-full bg-info-soft text-xs font-bold text-navy">2</span> Potongan teks disimpan dan bisa dicari siswa lewat Sekar AI.</li>
                <li class="flex gap-3"><span class="grid size-6 shrink-0 place-items-center rounded-full bg-info-soft text-xs font-bold text-navy">3</span> Jawaban selalu menyebut sumber materi dan halaman.</li>
            </ol>
        </x-card>
    </div>
</div>

<x-modal name="tambah-berkas" judul="Tambah PDF">
    <form method="POST" action="{{ route('guru.berkas.store', $materi) }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <x-input-file name="berkas[]" multiple accept=".pdf,application/pdf" :maks-mb="(int) round(config('lms.unggah.materi_kb') / 1024)" required />
        <div class="flex justify-end gap-3">
            <button type="button" class="btn-secondary" @click="$dispatch('tutup-modal', 'tambah-berkas')">Batal</button>
            <button type="submit" class="btn-primary" data-memuat="Mengunggah...">Unggah</button>
        </div>
    </form>
</x-modal>
@endsection
