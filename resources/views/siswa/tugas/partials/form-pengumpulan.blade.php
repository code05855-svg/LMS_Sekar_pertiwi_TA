{{-- Kartu pengumpulan siswa: belum kumpul, sudah kumpul (boleh ganti/tarik), atau sudah dinilai. --}}
@php
    $ekstensi = collect(config('lms.unggah.ekstensi_tugas'));
    $maksMb = (int) round(config('lms.unggah.tugas_kb') / 1024);
    $terlambat = $tugas->tenggat->isPast();
@endphp

<x-card judul="Pengumpulan">
    @if (! $pengumpulan)
        <p class="text-sm text-muted">Kamu belum mengumpulkan tugas ini.</p>

        @if ($terlambat)
            <x-alert tipe="peringatan" class="mt-4">Tenggat sudah lewat. Kamu masih bisa mengumpulkan, tetapi akan tercatat <strong>terlambat</strong>.</x-alert>
        @endif

        <form method="POST" action="{{ route('siswa.pengumpulan-tugas.store', $tugas) }}" enctype="multipart/form-data" class="mt-4 space-y-4">
            @csrf
            <x-input-file name="berkas" label="Berkas jawaban" :accept="$ekstensi->map(fn ($e) => '.'.$e)->implode(',')" :maks-mb="$maksMb" required
                :bantuan="'Format: '.$ekstensi->implode(', ').'. Maksimal '.$maksMb.' MB.'" />
            <button type="submit" class="btn-primary w-full" data-memuat="Mengunggah...">
                <x-ikon nama="unggah" class="size-4" /> Kumpulkan tugas
            </button>
        </form>
    @else
        <div class="flex flex-wrap items-center gap-3 rounded-xl bg-canvas p-4">
            <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-info-soft text-navy"><x-ikon nama="dokumen" class="size-5" /></span>
            <div class="min-w-0 flex-1">
                <p class="truncate font-semibold text-ink">{{ $pengumpulan->nama_asli }}</p>
                <p class="text-xs text-muted">{{ \App\Support\Format::ukuran($pengumpulan->ukuran) }} · {{ $pengumpulan->waktu_kumpul->translatedFormat('d M Y, H:i') }}</p>
            </div>
            <x-badge-status :status="$pengumpulan->status" />
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            <a href="{{ route('siswa.pengumpulan-tugas.unduh', $pengumpulan) }}" class="btn-secondary btn-sm"><x-ikon nama="unduh" class="size-4" /> Unduh jawabanku</a>

            @unless ($pengumpulan->telahDinilai())
                <button type="button" class="btn-secondary btn-sm" @click="$dispatch('buka-modal', 'ganti-jawaban')"><x-ikon nama="ubah" class="size-4" /> Ganti berkas</button>
                <form method="POST" action="{{ route('siswa.pengumpulan-tugas.destroy', $pengumpulan) }}" data-konfirmasi="Jawaban yang sudah dikumpulkan akan ditarik. Kamu bisa mengumpulkannya lagi, tetapi jika lewat tenggat akan tercatat terlambat." data-judul="Tarik jawaban?" data-tombol="Tarik jawaban">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-danger-soft btn-sm"><x-ikon nama="hapus" class="size-4" /> Tarik</button>
                </form>
            @endunless
        </div>

        @unless ($pengumpulan->telahDinilai())
            <p class="mt-4 text-xs text-muted">Kamu masih bisa mengganti berkas sampai gurumu menilainya. Mengganti berkas memperbarui waktu pengumpulan.</p>

            <x-modal name="ganti-jawaban" judul="Ganti berkas jawaban">
                <form method="POST" action="{{ route('siswa.pengumpulan-tugas.update', $pengumpulan) }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf @method('PUT')
                    @if ($terlambat)
                        <x-alert tipe="peringatan">Tenggat sudah lewat, jadi pengumpulan ulang tercatat <strong>terlambat</strong>.</x-alert>
                    @endif
                    <x-input-file name="berkas" :accept="$ekstensi->map(fn ($e) => '.'.$e)->implode(',')" :maks-mb="$maksMb" required :bantuan="'Format: '.$ekstensi->implode(', ').'. Maksimal '.$maksMb.' MB.'" />
                    <div class="flex justify-end gap-3">
                        <button type="button" class="btn-secondary" @click="$dispatch('tutup-modal', 'ganti-jawaban')">Batal</button>
                        <button type="submit" class="btn-primary" data-memuat="Mengunggah...">Ganti berkas</button>
                    </div>
                </form>
            </x-modal>
        @endunless
    @endif
</x-card>
