{{--
    Modal Alpine. Buka dengan: <button @click="$dispatch('buka-modal', 'nama')">
    atau dengan x-data="{ buka: false }" di induk dan <x-modal name="..." />.
--}}
@props(['name', 'judul' => null, 'lebar' => 'max-w-lg'])

<div x-data="{ terbuka: false }"
     x-on:buka-modal.window="if ($event.detail === '{{ $name }}') terbuka = true"
     x-on:tutup-modal.window="if (!$event.detail || $event.detail === '{{ $name }}') terbuka = false"
     x-on:keydown.escape.window="terbuka = false"
     x-show="terbuka" x-cloak
     class="fixed inset-0 z-[60] grid place-items-center p-4" role="dialog" aria-modal="true"
     @if ($judul) aria-labelledby="modal-{{ $name }}" @endif>
    <div x-show="terbuka" x-transition.opacity class="absolute inset-0 bg-navy-900/50" @click="terbuka = false"></div>

    <div x-show="terbuka" x-transition.scale.95 class="relative w-full {{ $lebar }} rounded-2xl bg-white p-6 shadow-(--shadow-pop)">
        <div class="flex items-start justify-between gap-4">
            @if ($judul)
                <h2 id="modal-{{ $name }}" class="text-lg font-bold text-ink">{{ $judul }}</h2>
            @endif
            <button type="button" class="btn-icon -mr-2 -mt-2 ml-auto" @click="terbuka = false" aria-label="Tutup">
                <x-ikon nama="tutup" class="size-5" />
            </button>
        </div>
        <div class="mt-3">{{ $slot }}</div>
    </div>
</div>
