{{-- Lencana status tugas dari sisi siswa. Butuh $tugas dan $pengumpulan (boleh null). --}}
@if ($pengumpulan?->telahDinilai())
    <span class="badge-success">Dinilai</span>
@elseif ($pengumpulan)
    <span class="badge-info">Dikumpulkan</span>
    @if ($pengumpulan->status === \App\Enums\StatusPengumpulan::Terlambat)<span class="badge-danger">Terlambat</span>@endif
@elseif ($tugas->tenggat->isPast())
    <span class="badge-danger">Lewat tenggat</span>
@else
    <span class="badge-gold">Belum dikumpulkan</span>
@endif
