{{-- Formulir balasan di bawah topik, atau catatan bila topik dikunci. --}}
@if ($topik->dikunci)
    <div class="flex items-center gap-3 border-t border-line bg-canvas px-5 py-4 text-sm text-muted sm:px-6">
        <x-ikon nama="kunci" class="size-5 shrink-0" /> Topik ini dikunci oleh guru. Balasan baru tidak dapat ditambahkan.
    </div>
@else
    <form method="POST" action="{{ route('forum.balasan.store', $topik) }}" class="border-t border-line p-5 sm:p-6" id="balas">
        @csrf
        <div class="flex gap-4">
            <div class="hidden sm:block"><x-avatar :nama="auth()->user()->nama" ukuran="size-10 text-sm" /></div>
            <div class="min-w-0 flex-1">
                <x-field type="textarea" name="isi" label="Tulis balasan" :value="old('isi')" rows="4" required maxlength="5000" placeholder="Bagikan jawaban atau pendapatmu..." />
                <div class="mt-3 flex justify-end">
                    <button type="submit" class="btn-primary" data-memuat="Mengirim..."><x-ikon nama="kirim" class="size-4" /> Kirim balasan</button>
                </div>
            </div>
        </div>
    </form>
@endif
