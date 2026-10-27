<x-card judul="Panduan diskusi">
    <ul class="space-y-3 text-sm text-muted">
        @foreach (['Gunakan bahasa yang sopan.', 'Tulis judul yang jelas supaya mudah dicari.', 'Jangan bagikan data pribadi.', 'Jawaban tugas jangan ditempel di forum.'] as $butir)
            <li class="flex gap-3"><x-ikon nama="centang" class="mt-0.5 size-4 shrink-0 text-success" /> {{ $butir }}</li>
        @endforeach
    </ul>
</x-card>
