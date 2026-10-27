{{-- Satu baris berkas PDF: status indeks chatbot dan aksi (lihat, unduh, ganti, indeks ulang, hapus). --}}
@php
    $status = $berkas->status_indeks;
    $gagal = $status === \App\Enums\StatusIndeks::Gagal;
    $sibuk = in_array($status, [\App\Enums\StatusIndeks::Menunggu, \App\Enums\StatusIndeks::Diproses], true);
@endphp

<li class="px-5 py-4">
    <div class="flex flex-wrap items-center gap-3">
        <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-danger-soft text-danger"><x-ikon nama="dokumen" class="size-5" /></span>
        <div class="min-w-0 flex-1">
            <p class="truncate font-semibold text-ink">{{ $berkas->nama_asli }}</p>
            <p class="text-xs text-muted">
                {{ \App\Support\Format::ukuran($berkas->ukuran) }}
                @if ($berkas->jumlah_halaman)· {{ $berkas->jumlah_halaman }} halaman @endif
                @if ($berkas->diindeks_pada)· diindeks {{ $berkas->diindeks_pada->diffForHumans() }}@endif
            </p>
        </div>
        <x-badge-status :status="$status" />

        <div class="flex items-center gap-1">
            <a href="{{ route('guru.berkas.show', $berkas) }}" target="_blank" rel="noopener" class="btn-icon" title="Buka PDF" aria-label="Buka {{ $berkas->nama_asli }}"><x-ikon nama="lihat" class="size-[18px]" /></a>
            <a href="{{ route('guru.berkas.show', [$berkas, 'unduh' => 1]) }}" class="btn-icon" title="Unduh" aria-label="Unduh {{ $berkas->nama_asli }}"><x-ikon nama="unduh" class="size-[18px]" /></a>
            <button type="button" class="btn-icon" title="Ganti PDF" aria-label="Ganti {{ $berkas->nama_asli }}" @click="$dispatch('buka-modal', 'ganti-{{ $berkas->id }}')"><x-ikon nama="ubah" class="size-[18px]" /></button>
            <form method="POST" action="{{ route('guru.berkas.reindeks', $berkas) }}">
                @csrf
                <button type="submit" class="btn-icon" title="Indeks ulang untuk chatbot" aria-label="Indeks ulang {{ $berkas->nama_asli }}"><x-ikon nama="segarkan" class="size-[18px]" /></button>
            </form>
            <form method="POST" action="{{ route('guru.berkas.destroy', $berkas) }}" data-konfirmasi="Berkas “{{ $berkas->nama_asli }}” dan indeks chatbot-nya akan dihapus permanen." data-judul="Hapus berkas?" data-tombol="Hapus">
                @csrf @method('DELETE')
                <button type="submit" class="btn-icon hover:!bg-danger-soft hover:!text-danger" title="Hapus" aria-label="Hapus {{ $berkas->nama_asli }}"><x-ikon nama="hapus" class="size-[18px]" /></button>
            </form>
        </div>
    </div>

    @if ($gagal)
        <p class="mt-3 flex items-start gap-2 rounded-xl bg-danger-soft px-3.5 py-2.5 text-xs text-danger">
            <x-ikon nama="peringatan" class="mt-px size-4 shrink-0" />
            <span><strong>Pengindeksan gagal:</strong> {{ rtrim($berkas->pesan_galat ?: 'Penyebab tidak diketahui', '.') }}. Coba tombol indeks ulang, atau ganti dengan PDF yang berisi teks.</span>
        </p>
    @elseif ($sibuk)
        <p class="mt-3 flex items-center gap-2 rounded-xl bg-gold-soft px-3.5 py-2.5 text-xs text-[#8a6500]">
            <x-ikon nama="jam" class="size-4 shrink-0" /> Sedang diproses n8n. Muat ulang halaman beberapa saat lagi.
        </p>
    @endif

    <x-modal name="ganti-{{ $berkas->id }}" judul="Ganti PDF">
        <form method="POST" action="{{ route('guru.berkas.update', $berkas) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf @method('PUT')
            <p class="text-sm text-muted">Indeks chatbot lama dibuang, lalu berkas baru diindeks ulang.</p>
            <x-input-file name="berkas" accept=".pdf,application/pdf" :maks-mb="(int) round(config('lms.unggah.materi_kb') / 1024)" required />
            <div class="flex justify-end gap-3">
                <button type="button" class="btn-secondary" @click="$dispatch('tutup-modal', 'ganti-{{ $berkas->id }}')">Batal</button>
                <button type="submit" class="btn-primary" data-memuat="Mengunggah...">Ganti PDF</button>
            </div>
        </form>
    </x-modal>
</li>
