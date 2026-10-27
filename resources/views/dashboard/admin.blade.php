@extends('layouts.app')

@section('judul', 'Dashboard Admin')

@section('konten')
<x-judul-halaman judul="Dashboard Admin" deskripsi="Ringkasan data dasar sekolah dan status indeks chatbot." />

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-kartu-stat label="Guru aktif" :nilai="$statistik['guru']" catatan="Akun pengajar" warna="navy" :href="route('admin.guru.index')" />
    <x-kartu-stat label="Siswa aktif" :nilai="$statistik['siswa']" catatan="Peserta didik" warna="success" :href="route('admin.siswa.index')" />
    <x-kartu-stat label="Kelas" :nilai="$statistik['kelas']" :catatan="$statistik['mapel'].' mata pelajaran'" warna="gold" :href="route('admin.kelas.index')" />
    <x-kartu-stat label="Penugasan guru" :nilai="$statistik['penugasan']" catatan="Guru yang sudah ditempatkan" warna="danger" :href="route('admin.penugasan-guru.index')" />
</div>

@if ($siswaTanpaKelas > 0 || $kelasTanpaGuru > 0)
    <div class="mt-5 space-y-3">
        @if ($siswaTanpaKelas > 0)
            <x-alert tipe="peringatan">
                <strong>{{ $siswaTanpaKelas }} siswa aktif</strong> belum ditempatkan di kelas, sehingga belum melihat mata pelajaran apa pun.
                <a href="{{ route('admin.penempatan-siswa.index') }}" class="tautan ml-1">Tempatkan sekarang</a>
            </x-alert>
        @endif
        @if ($kelasTanpaGuru > 0)
            <x-alert tipe="info">
                <strong>{{ $kelasTanpaGuru }} kelas</strong> belum punya guru pengampu.
                <a href="{{ route('admin.penugasan-guru.create') }}" class="tautan ml-1">Atur penugasan guru</a>
            </x-alert>
        @endif
    </div>
@endif

<div class="mt-5 grid gap-5 lg:grid-cols-3">
    <x-card judul="Kelas" deskripsi="Jumlah siswa dan mata pelajaran per kelas." class="lg:col-span-2" :tanpa-padding="true">
        <x-slot:aksi>
            <a href="{{ route('admin.kelas.create') }}" class="btn-primary btn-sm"><x-ikon nama="tambah" class="size-4" /> Tambah kelas</a>
        </x-slot:aksi>

        @if ($daftarKelas->isEmpty())
            <x-kosong judul="Belum ada kelas" deskripsi="Mulai dengan menambahkan kelas, lalu mata pelajaran, guru, dan siswa." ikon="kelas">
                <a href="{{ route('admin.kelas.create') }}" class="btn-primary">Tambah kelas pertama</a>
            </x-kosong>
        @else
            <x-tabel-data :kolom="['Kelas', 'Tahun ajaran', 'Siswa', 'Mapel', '']" class="px-3 pb-3">
                @foreach ($daftarKelas as $k)
                    <tr>
                        <td class="font-semibold text-ink">{{ $k->tingkat->value }} {{ $k->nama }}</td>
                        <td class="text-muted">{{ $k->tahun_ajaran }}</td>
                        <td>{{ $k->siswa_count }}</td>
                        <td>{{ $k->penugasan_guru_count }}</td>
                        <td class="text-right"><a href="{{ route('admin.kelas.show', $k) }}" class="tautan text-sm">Detail →</a></td>
                    </tr>
                @endforeach
            </x-tabel-data>
        @endif
    </x-card>

    <div class="space-y-5">
        <x-card judul="Chatbot Sekar AI" deskripsi="Status indeks dokumen materi.">
            <dl class="space-y-3 text-sm">
                <div class="flex items-center justify-between"><dt class="text-muted">Berkas terindeks</dt><dd><x-badge-status status="terindeks">{{ $chatbot['terindeks'] }}</x-badge-status></dd></div>
                <div class="flex items-center justify-between"><dt class="text-muted">Menunggu / diproses</dt><dd><x-badge-status status="menunggu">{{ $chatbot['proses'] }}</x-badge-status></dd></div>
                <div class="flex items-center justify-between"><dt class="text-muted">Gagal diindeks</dt><dd><x-badge-status status="gagal">{{ $chatbot['gagal'] }}</x-badge-status></dd></div>
                <div class="my-1 border-t border-line"></div>
                <div class="flex items-center justify-between"><dt class="text-muted">Total pertanyaan</dt><dd class="font-semibold">{{ $chatbot['pertanyaan'] }}</dd></div>
                <div class="flex items-center justify-between"><dt class="text-muted">Pertanyaan gagal</dt><dd class="font-semibold">{{ $chatbot['pertanyaan_gagal'] }}</dd></div>
            </dl>
        </x-card>

        <x-card judul="Akun terbaru">
            @forelse ($akunTerbaru as $akun)
                <div class="flex items-center gap-3 py-2 {{ $loop->first ? '' : 'border-t border-line' }}">
                    <x-avatar :nama="$akun->nama" ukuran="size-9 text-xs" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-ink">{{ $akun->nama }}</p>
                        <p class="text-xs text-muted">{{ $akun->nomor_induk }} · {{ $akun->created_at?->diffForHumans() }}</p>
                    </div>
                    <x-badge-status :status="$akun->peran" />
                </div>
            @empty
                <p class="text-sm text-muted">Belum ada akun guru atau siswa.</p>
            @endforelse
        </x-card>
    </div>
</div>
@endsection
