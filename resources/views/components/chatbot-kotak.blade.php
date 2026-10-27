{{--
    Kotak percakapan Sekar AI (dipakai halaman Chatbot dan widget melayang).
    Pesan dikirim lewat fetch; logika di resources/js/chatbot.js.
--}}
@props(['percakapan' => collect(), 'saran' => collect(), 'muatAwal' => false])

<div x-data="sekarAi({ url: '{{ route('chatbot.kirim') }}', muatUrl: '{{ route('chatbot.index', ['fragmen' => 1]) }}', muatAwal: {{ $muatAwal ? 'true' : 'false' }} })"
     x-on:sekar-ai-dibuka.window="muat()"
     {{ $attributes->merge(['class' => 'flex min-h-0 flex-col']) }}>

    <div x-ref="daftar" class="scroll-tipis min-h-0 flex-1 space-y-4 overflow-y-auto px-4 py-4" aria-live="polite">
        @if ($percakapan->isEmpty())
            <div x-ref="kosong" class="gelembung-bot">
                Halo! Aku <strong>Sekar AI</strong>, asisten akademik SMP Sekar Pertiwi. Tanyakan isi materi pelajaran dari gurumu,
                atau cara memakai LMS ini. Aku menjawab dari materi yang sudah terbit di kelasmu.
            </div>
        @else
            @include('chatbot.partials.percakapan', ['percakapan' => $percakapan])
        @endif
    </div>

    @if ($saran->isNotEmpty())
        <div x-ref="saran" class="px-4 pb-2 {{ $percakapan->isNotEmpty() ? 'hidden' : '' }}">
            <p class="mb-2 text-xs font-semibold text-ink">Coba tanyakan</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($saran as $s)
                    <button type="button" @click="tanyaSaran(@js($s))"
                            class="rounded-full bg-canvas px-3 py-1.5 text-left text-xs font-medium text-navy ring-1 ring-inset ring-line transition hover:bg-info-soft">
                        {{ $s }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    <form class="px-4 pb-3 pt-2" @submit.prevent="kirim()">
        <div class="flex items-end gap-2 rounded-2xl border border-line bg-white py-1.5 pl-4 pr-1.5 transition focus-within:border-navy focus-within:ring-2 focus-within:ring-navy/15">
            <label for="pertanyaan-{{ $attributes->get('id', 'sekar') }}" class="sr-only">Tulis pertanyaanmu</label>
            <textarea id="pertanyaan-{{ $attributes->get('id', 'sekar') }}" x-ref="masukan" x-model="pertanyaan" rows="1" maxlength="500"
                      placeholder="Tulis pertanyaanmu..." :disabled="memuat"
                      @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); kirim(); }"
                      @input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 112) + 'px'"
                      class="max-h-28 min-h-9 flex-1 resize-none border-0 bg-transparent py-2 text-sm text-ink placeholder:text-muted focus:outline-none focus:ring-0 disabled:opacity-60"></textarea>
            <button type="submit" data-tetap :disabled="memuat || pertanyaan.trim().length < 3"
                    class="grid size-9 shrink-0 place-items-center rounded-full bg-navy text-white transition hover:bg-navy-700 disabled:cursor-not-allowed disabled:opacity-40"
                    aria-label="Kirim pertanyaan">
                <x-ikon nama="panah-atas" class="size-5" />
            </button>
        </div>
        <p class="mt-2 text-[11px] text-muted">AI dapat keliru. Periksa kembali dengan guru. <span x-show="pertanyaan.length > 400" x-cloak x-text="`(${pertanyaan.length}/500)`"></span></p>
    </form>
</div>
