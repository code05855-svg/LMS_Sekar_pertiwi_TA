{{-- Daftar sumber materi yang dipakai jawaban (judul materi, berkas, halaman). --}}
@php
    $pengguna = auth()->user();
    $tautan = fn ($id) => $id
        ? ($pengguna->adalahGuru() ? route('guru.materi.show', $id) : route('siswa.materi.show', $id))
        : null;
@endphp

<div class="mt-3 border-t border-navy/10 pt-2">
    <p class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-success">Sumber</p>
    <ul class="space-y-1">
        @foreach ($sumber as $i => $s)
            @php($url = $tautan($s['materi_id'] ?? null))
            <li class="text-xs leading-snug text-success">
                <span class="font-semibold">[{{ $s['no'] ?? $i + 1 }}]</span>
                @if ($url)
                    <a href="{{ $url }}" class="font-semibold underline-offset-2 hover:underline">{{ $s['materi'] ?? 'Materi' }}</a>
                @else
                    <span class="font-semibold">{{ $s['materi'] ?? 'Materi' }}</span>
                @endif
                @if (! empty($s['mapel']))· {{ $s['mapel'] }}@endif
                @if (! empty($s['berkas']))· {{ $s['berkas'] }}@endif
                @if (! empty($s['halaman']))· halaman {{ $s['halaman'] }}@endif
            </li>
        @endforeach
    </ul>
</div>
