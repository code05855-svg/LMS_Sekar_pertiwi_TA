{{-- Kartu pengumpulan siswa: belum kumpul, sudah kumpul (boleh ganti/tarik), atau sudah dinilai. --}}
@php
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
            @include('siswa.tugas.partials.isian-jawaban', ['pengumpulan' => null])
            <button type="submit" class="btn-primary w-full" data-memuat="Mengirim...">
                <x-ikon nama="unggah" class="size-4" /> Kumpulkan tugas
            </button>
        </form>
    @else
        <x-pratinjau-jawaban :pengumpulan="$pengumpulan" :kompak="true"
            :url-lihat="$pengumpulan->berupaBerkas() ? route('siswa.pengumpulan-tugas.unduh', [$pengumpulan, 'lihat' => 1]) : null"
            :url-unduh="$pengumpulan->berupaBerkas() ? route('siswa.pengumpulan-tugas.unduh', $pengumpulan) : null" />
 
        @unless ($pengumpulan->telahDinilai())
            <div class="mt-4 flex flex-wrap gap-2">
                <button type="button" class="btn-secondary btn-sm" @click="$dispatch('buka-modal', 'ganti-jawaban')"><x-ikon nama="ubah" class="size-4" /> Ganti jawaban</button>
                <form method="POST" action="{{ route('siswa.pengumpulan-tugas.destroy', $pengumpulan) }}" data-konfirmasi="Jawaban yang sudah dikumpulkan akan ditarik. Kamu bisa mengumpulkannya lagi, tetapi jika lewat tenggat akan tercatat terlambat." data-judul="Tarik jawaban?" data-tombol="Tarik jawaban">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-danger-soft btn-sm"><x-ikon nama="hapus" class="size-4" /> Tarik</button>
                </form>
            </div>
 
            <p class="mt-4 text-xs text-muted">Kamu masih bisa mengganti jawaban (termasuk berganti jenis) sampai gurumu menilainya. Mengganti jawaban memperbarui waktu pengumpulan.</p>
 
            <x-modal name="ganti-jawaban" judul="Ganti jawaban">
                <form method="POST" action="{{ route('siswa.pengumpulan-tugas.update', $pengumpulan) }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf @method('PUT')
                    @if ($terlambat)
                        <x-alert tipe="peringatan">Tenggat sudah lewat, jadi pengumpulan ulang tercatat <strong>terlambat</strong>.</x-alert>
                    @endif
                    @include('siswa.tugas.partials.isian-jawaban', ['awal' => $pengumpulan->jenis->value])
                    <div class="flex justify-end gap-3">
                        <button type="button" class="btn-secondary" @click="$dispatch('tutup-modal', 'ganti-jawaban')">Batal</button>
                        <button type="submit" class="btn-primary" data-memuat="Mengirim...">Ganti jawaban</button>
                    </div>
                </form>
            </x-modal>
        @endunless
    @endif
</x-card>
