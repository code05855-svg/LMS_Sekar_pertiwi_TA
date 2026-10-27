{{-- Tombol Sekar AI melayang di kanan bawah semua halaman siswa dan guru. --}}
<div x-data="{ buka: false }" class="fixed bottom-5 right-4 z-50 flex flex-col items-end gap-3 sm:right-6" @keydown.escape.window="buka = false">
    <section x-show="buka" x-cloak x-transition.origin.bottom.right
             class="flex h-[min(34rem,calc(100dvh-8rem))] w-[min(24rem,calc(100vw-2rem))] flex-col overflow-hidden rounded-2xl border border-line bg-white shadow-(--shadow-pop)"
             aria-label="Sekar AI, asisten akademik">
        <header class="flex items-center gap-3 bg-navy px-4 py-3 text-white">
            <span class="grid size-10 shrink-0 place-items-center rounded-full bg-gold text-navy"><x-ikon nama="robot" class="size-6" /></span>
            <div class="min-w-0 flex-1 leading-tight">
                <p class="font-bold">Sekar AI</p>
                <p class="text-xs text-white/70">Asisten akademik</p>
            </div>
            <span class="rounded-full bg-success px-2.5 py-1 text-[11px] font-semibold">Online</span>
            <a href="{{ route('chatbot.index') }}" class="btn-icon text-white/80 hover:bg-white/10 hover:text-white" title="Buka halaman penuh" aria-label="Buka halaman penuh">
                <x-ikon nama="lihat" class="size-[18px]" />
            </a>
            <button type="button" class="btn-icon text-white/80 hover:bg-white/10 hover:text-white" @click="buka = false" aria-label="Tutup chatbot">
                <x-ikon nama="tutup" class="size-5" />
            </button>
        </header>

        <x-chatbot-kotak id="widget" class="flex-1" :saran="\App\Models\Faq::aktif()->orderBy('id')->limit(3)->pluck('pertanyaan')" />
    </section>

    <div class="flex items-center gap-3">
        <span x-show="!buka" x-transition.opacity class="hidden rounded-xl border border-line bg-white px-3.5 py-2 text-sm font-semibold text-ink shadow-(--shadow-card) sm:block">Tanya Sekar AI</span>
        <button type="button" @click="buka = !buka; if (buka) $dispatch('sekar-ai-dibuka')"
                class="grid size-14 place-items-center rounded-full bg-navy text-white shadow-(--shadow-pop) ring-4 ring-white transition hover:-translate-y-0.5 hover:bg-navy-700"
                :aria-expanded="buka" aria-label="Buka chatbot Sekar AI">
            <x-ikon x-show="!buka" nama="robot" class="size-8" />
            <x-ikon x-show="buka" x-cloak nama="bawah" class="size-7" />
        </button>
    </div>
</div>
