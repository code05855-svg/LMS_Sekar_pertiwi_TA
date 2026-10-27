@extends('layouts.app')

@section('judul', 'Penempatan Siswa')

@section('konten')
<x-judul-halaman judul="Penempatan siswa" deskripsi="Tempatkan siswa ke kelas. Satu siswa berada di satu kelas; menempatkan ke kelas lain berarti memindahkan." />

<div class="grid gap-5 xl:grid-cols-5">
    {{-- Daftar kelas --}}
    <x-card judul="Pilih kelas" class="xl:col-span-2" :tanpa-padding="true">
        @if ($daftarKelas->isEmpty())
            <x-kosong judul="Belum ada kelas" ikon="kelas"><a href="{{ route('admin.kelas.create') }}" class="btn-primary">Tambah kelas</a></x-kosong>
        @else
            <ul class="scroll-tipis max-h-[34rem] divide-y divide-line overflow-y-auto px-3 pb-3">
                @foreach ($daftarKelas as $k)
                    @php($dipilih = $kelasDipilih?->id === $k->id)
                    <li>
                        <a href="{{ route('admin.penempatan-siswa.index', ['kelas' => $k->id]) }}" @if ($dipilih) aria-current="true" @endif
                           class="flex items-center justify-between gap-3 rounded-xl px-3 py-3 transition {{ $dipilih ? 'bg-info-soft' : 'hover:bg-canvas' }}">
                            <span>
                                <span class="block font-semibold text-ink">{{ $k->tingkat->value }} {{ $k->nama }}</span>
                                <span class="block text-xs text-muted">{{ $k->tahun_ajaran }}</span>
                            </span>
                            <span class="badge-info">{{ $k->siswa_count }} siswa</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>

    <div class="space-y-5 xl:col-span-3">
        @if ($kelasDipilih)
            <x-card :judul="'Siswa di kelas '.$kelasDipilih->tingkat->value.' '.$kelasDipilih->nama" :deskripsi="$siswaDiKelas->count().' siswa'" :tanpa-padding="true">
                @if ($siswaDiKelas->isEmpty())
                    <x-kosong judul="Kelas masih kosong" deskripsi="Pilih siswa pada daftar di bawah, lalu tempatkan ke kelas ini." ikon="pengguna" />
                @else
                    <ul class="scroll-tipis max-h-72 divide-y divide-line overflow-y-auto px-5 pb-3">
                        @foreach ($siswaDiKelas as $s)
                            <li class="flex items-center gap-3 py-2.5">
                                <x-avatar :nama="$s->nama" ukuran="size-9 text-xs" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-ink">{{ $s->nama }}</p>
                                    <p class="text-xs text-muted">{{ $s->nomor_induk }}</p>
                                </div>
                                <form method="POST" action="{{ route('admin.penempatan-siswa.destroy', $s) }}" data-konfirmasi="{{ $s->nama }} akan dikeluarkan dari kelas {{ $kelasDipilih->nama }} dan tidak lagi melihat mata pelajarannya." data-judul="Keluarkan dari kelas?" data-tombol="Keluarkan">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-ghost btn-sm text-danger hover:!bg-danger-soft">Keluarkan</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>

            <x-card judul="Tempatkan siswa ke kelas ini" deskripsi="Siswa yang belum punya kelas.">
                @if ($siswaBelumPunyaKelas->isEmpty())
                    <p class="text-sm text-muted">Semua siswa aktif sudah punya kelas. Untuk memindahkan siswa, ubah kelasnya di
                        <a href="{{ route('admin.siswa.index') }}" class="tautan">daftar siswa</a>.</p>
                @else
                    <form method="POST" action="{{ route('admin.penempatan-siswa.store') }}" x-data="{ semua: false }">
                        @csrf
                        <input type="hidden" name="kelas_id" value="{{ $kelasDipilih->id }}">
                        <label class="mb-2 flex items-center gap-2 text-sm font-semibold text-ink">
                            <input type="checkbox" class="size-4 rounded border-line text-navy focus:ring-navy" x-model="semua"
                                   @change="$root.querySelectorAll('input[name=\'siswa_ids[]\']').forEach(c => c.checked = semua)">
                            Pilih semua ({{ $siswaBelumPunyaKelas->count() }})
                        </label>
                        <ul class="scroll-tipis max-h-72 divide-y divide-line overflow-y-auto rounded-xl border border-line">
                            @foreach ($siswaBelumPunyaKelas as $s)
                                <li>
                                    <label class="flex cursor-pointer items-center gap-3 px-3 py-2.5 hover:bg-canvas">
                                        <input type="checkbox" name="siswa_ids[]" value="{{ $s->id }}" class="size-4 rounded border-line text-navy focus:ring-navy" @checked(in_array($s->id, old('siswa_ids', [])))>
                                        <span class="min-w-0 flex-1 truncate text-sm font-semibold text-ink">{{ $s->nama }}</span>
                                        <span class="text-xs text-muted">{{ $s->nomor_induk }}</span>
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                        <div class="mt-4 flex justify-end">
                            <button type="submit" data-memuat="Menempatkan..." class="btn-primary">Tempatkan ke kelas {{ $kelasDipilih->nama }}</button>
                        </div>
                    </form>
                @endif
            </x-card>
        @else
            <x-card>
                <x-kosong judul="Pilih kelas terlebih dahulu" deskripsi="Pilih kelas di sebelah kiri untuk melihat dan mengatur siswanya." ikon="pindah" />
            </x-card>

            @if ($siswaBelumPunyaKelas->isNotEmpty())
                <x-alert tipe="peringatan"><strong>{{ $siswaBelumPunyaKelas->count() }} siswa aktif</strong> belum punya kelas.</x-alert>
            @endif
        @endif
    </div>
</div>
@endsection
