@extends('layouts.app')

@section('judul', 'Tambah Penugasan Guru')

@section('konten')
<x-judul-halaman judul="Tambah penugasan guru" deskripsi="Satu mata pelajaran di satu kelas diampu satu guru." :kembali="route('admin.penugasan-guru.index')" label="Daftar penugasan" />

@if ($daftarGuru->isEmpty() || $daftarKelas->isEmpty() || $daftarMapel->isEmpty())
    <x-alert tipe="peringatan" class="mb-5 max-w-3xl" judul="Data dasar belum lengkap">
        Penugasan membutuhkan minimal satu guru aktif, satu kelas, dan satu mata pelajaran.
        @if ($daftarGuru->isEmpty())<a href="{{ route('admin.guru.create') }}" class="tautan ml-1">Tambah guru</a>@endif
        @if ($daftarKelas->isEmpty())<a href="{{ route('admin.kelas.create') }}" class="tautan ml-1">Tambah kelas</a>@endif
        @if ($daftarMapel->isEmpty())<a href="{{ route('admin.mata-pelajaran.create') }}" class="tautan ml-1">Tambah mata pelajaran</a>@endif
    </x-alert>
@endif

<x-card class="max-w-3xl">
    <form method="POST" action="{{ route('admin.penugasan-guru.store') }}" class="space-y-5">
        @csrf
        <x-field type="select" name="guru_id" label="Guru pengampu" required>
            <option value="">Pilih guru</option>
            @foreach ($daftarGuru as $g)
                <option value="{{ $g->id }}" @selected((string) old('guru_id', $pilihan['guru_id'] ?? '') === (string) $g->id)>{{ $g->nama }} ({{ $g->nomor_induk }})</option>
            @endforeach
        </x-field>
        <div class="grid gap-5 sm:grid-cols-2">
            <x-field type="select" name="kelas_id" label="Kelas" required>
                <option value="">Pilih kelas</option>
                @foreach ($daftarKelas as $k)
                    <option value="{{ $k->id }}" @selected((string) old('kelas_id', $pilihan['kelas_id'] ?? '') === (string) $k->id)>{{ $k->tingkat->value }} {{ $k->nama }} · {{ $k->tahun_ajaran }}</option>
                @endforeach
            </x-field>
            <x-field type="select" name="mata_pelajaran_id" label="Mata pelajaran" required>
                <option value="">Pilih mata pelajaran</option>
                @foreach ($daftarMapel as $m)
                    <option value="{{ $m->id }}" @selected((string) old('mata_pelajaran_id') === (string) $m->id)>{{ $m->nama }}</option>
                @endforeach
            </x-field>
        </div>
        <div class="flex justify-end gap-2">
            <a href="{{ route('admin.penugasan-guru.index') }}" class="btn-secondary">Batal</a>
            <button type="submit" data-memuat="Menyimpan..." class="btn-primary">Simpan penugasan</button>
        </div>
    </form>
</x-card>
@endsection
