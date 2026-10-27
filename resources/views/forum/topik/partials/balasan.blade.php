{{-- Satu balasan; pemilik dapat mengubah/menghapus, guru pengampu dapat menghapus (moderasi). --}}
@php
    $pengguna = auth()->user();
    $bisaUbah = $pengguna->can('update', $balasan);
    $bisaHapus = $pengguna->can('delete', $balasan);
    $milikGuru = $balasan->pembuat->adalahGuru();
@endphp

<article id="balasan-{{ $balasan->id }}" x-data="{ ubah: false }" class="scroll-mt-24 px-5 py-5 sm:px-6">
    <div class="flex gap-4">
        <x-avatar :nama="$balasan->pembuat->nama" ukuran="size-10 text-sm" />
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                <span class="font-semibold text-ink">{{ $balasan->pembuat->nama }}</span>
                @if ($milikGuru)<span class="badge-gold">Guru</span>@endif
                <span class="text-xs text-muted">{{ $balasan->created_at->diffForHumans() }}@if ($balasan->updated_at->gt($balasan->created_at->addMinute())) · diubah @endif</span>
            </div>

            <div x-show="!ubah">
                <p class="mt-2 whitespace-pre-line break-words text-sm leading-relaxed text-ink">{{ $balasan->isi }}</p>

                @if ($bisaUbah || $bisaHapus)
                    <div class="mt-3 flex gap-3 text-xs font-semibold">
                        @if ($bisaUbah)<button type="button" class="text-muted hover:text-navy" @click="ubah = true">Ubah</button>@endif
                        @if ($bisaHapus)
                            <form method="POST" action="{{ route('forum.balasan.destroy', $balasan) }}" data-konfirmasi="Balasan ini akan dihapus permanen." data-judul="Hapus balasan?" data-tombol="Hapus">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-muted hover:text-danger">Hapus</button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>

            @if ($bisaUbah)
                <form x-show="ubah" x-cloak method="POST" action="{{ route('forum.balasan.update', $balasan) }}" class="mt-2 space-y-3">
                    @csrf @method('PUT')
                    <label for="ubah-{{ $balasan->id }}" class="sr-only">Ubah balasan</label>
                    <textarea id="ubah-{{ $balasan->id }}" name="isi" rows="4" required maxlength="5000" class="input">{{ $balasan->isi }}</textarea>
                    <div class="flex gap-2">
                        <button type="submit" class="btn-primary btn-sm" data-memuat="Menyimpan...">Simpan</button>
                        <button type="button" class="btn-ghost btn-sm" @click="ubah = false">Batal</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</article>
