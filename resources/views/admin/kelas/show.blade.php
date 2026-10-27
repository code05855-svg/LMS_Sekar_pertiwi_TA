@extends('layouts.app')

@section('judul', 'Kelas '.$kelas->nama)

@section('konten')
<x-judul-halaman :judul="'Kelas '.$kelas->tingkat->value.' '.$kelas->nama" :deskripsi="'Tahun ajaran '.$kelas->tahun_ajaran.' · '.$kelas->siswa->count().' siswa · '.$kelas->penugasanGuru->count().' mata pelajaran'" :kembali="route('admin.kelas.index')" label="Daftar kelas">
    <a href="{{ route('admin.kelas.edit', $kelas) }}" class="btn-secondary"><x-ikon nama="ubah" class="size-4" /> Ubah</a>
</x-judul-halaman>

<div class="grid gap-5 lg:grid-cols-2">
    <x-card judul="Guru dan mata pelajaran" :tanpa-padding="true">
        <x-slot:aksi>
            <a href="{{ route('admin.penugasan-guru.create', ['kelas_id' => $kelas->id]) }}" class="btn-primary btn-sm"><x-ikon nama="tambah" class="size-4" /> Tambah</a>
        </x-slot:aksi>

        @if ($kelas->penugasanGuru->isEmpty())
            <x-kosong judul="Belum ada guru pengampu" deskripsi="Tentukan guru untuk tiap mata pelajaran di kelas ini." ikon="orang" />
        @else
            <ul class="divide-y divide-line px-5 pb-3">
                @foreach ($kelas->penugasanGuru->sortBy(fn ($p) => $p->mataPelajaran->nama) as $p)
                    <li class="flex items-center gap-3 py-3">
                        <x-sampul-mapel :nama="$p->mataPelajaran->nama" class="size-11 shrink-0 rounded-xl" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-ink">{{ $p->mataPelajaran->nama }}</p>
                            <p class="truncate text-xs text-muted">{{ $p->guru->nama }}</p>
                        </div>
                        <form method="POST" action="{{ route('admin.penugasan-guru.destroy', $p) }}" data-konfirmasi="{{ $p->guru->nama }} tidak lagi mengampu {{ $p->mataPelajaran->nama }} di kelas ini. Penugasan yang sudah punya materi, tugas, atau forum tidak bisa dihapus." data-judul="Hapus penugasan?" data-tombol="Hapus">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-icon hover:!bg-danger-soft hover:!text-danger" aria-label="Hapus penugasan {{ $p->mataPelajaran->nama }}"><x-ikon nama="hapus" class="size-[18px]" /></button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>

    <x-card judul="Siswa di kelas" :tanpa-padding="true">
        <x-slot:aksi>
            <a href="{{ route('admin.penempatan-siswa.index', ['kelas' => $kelas->id]) }}" class="btn-primary btn-sm"><x-ikon nama="pindah" class="size-4" /> Atur siswa</a>
        </x-slot:aksi>

        @if ($kelas->siswa->isEmpty())
            <x-kosong judul="Belum ada siswa" deskripsi="Tempatkan siswa lewat menu Penempatan Siswa." ikon="pengguna" />
        @else
            <ul class="scroll-tipis max-h-[28rem] divide-y divide-line overflow-y-auto px-5 pb-3">
                @foreach ($kelas->siswa as $s)
                    <li class="flex items-center gap-3 py-2.5">
                        <x-avatar :nama="$s->nama" ukuran="size-9 text-xs" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-ink">{{ $s->nama }}</p>
                            <p class="text-xs text-muted">{{ $s->nomor_induk }}</p>
                        </div>
                        @unless ($s->aktif)<x-badge-status status="nonaktif" />@endunless
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>
</div>
@endsection
