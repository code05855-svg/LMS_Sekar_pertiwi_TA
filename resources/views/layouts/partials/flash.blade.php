{{-- Pesan sesi (sukses, galat, info) sebagai toast; hilang sendiri setelah 7 detik. --}}
@php
    $pesan = collect([
        'sukses' => session('sukses'),
        'galat' => session('galat'),
        'info' => session('info'),
    ])->filter();
@endphp

@if ($pesan->isNotEmpty())
    <div class="pointer-events-none fixed right-4 top-20 z-50 w-[min(26rem,calc(100vw-2rem))] space-y-2" aria-live="polite">
        @foreach ($pesan as $tipe => $isi)
            <div x-data="{ tampil: true }" x-init="setTimeout(() => tampil = false, 7000)" x-show="tampil" x-transition.opacity.duration.300ms class="pointer-events-auto">
                <x-alert :tipe="$tipe" class="shadow-(--shadow-pop) ring-1 ring-black/5">
                    <div class="flex items-start justify-between gap-3">
                        <span>{{ $isi }}</span>
                        <button type="button" class="-mr-1 shrink-0 opacity-60 hover:opacity-100" @click="tampil = false" aria-label="Tutup pesan">
                            <x-ikon nama="tutup" class="size-4" />
                        </button>
                    </div>
                </x-alert>
            </div>
        @endforeach
    </div>
@endif

@if ($errors->any() && ! request()->routeIs('login'))
    <x-alert tipe="galat" class="mb-5" judul="Periksa kembali isian Anda">
        <ul class="mt-1 list-inside list-disc space-y-0.5">
            @foreach (collect($errors->all())->unique()->take(5) as $pesanGalat)
                <li>{{ $pesanGalat }}</li>
            @endforeach
        </ul>
    </x-alert>
@endif
