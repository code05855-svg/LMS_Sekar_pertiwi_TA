@php
    use App\Enums\PeranPengguna;

    $pengguna = auth()->user();

    $menu = match ($pengguna->peran) {
        PeranPengguna::Admin => [
            ['Dashboard', 'dashboard', 'dashboard', ['dashboard']],
            ['Guru', 'admin.guru.index', 'orang', ['admin.guru.*']],
            ['Siswa', 'admin.siswa.index', 'pengguna', ['admin.siswa.*']],
            ['Kelas', 'admin.kelas.index', 'kelas', ['admin.kelas.*']],
            ['Mata Pelajaran', 'admin.mata-pelajaran.index', 'buku', ['admin.mata-pelajaran.*']],
            ['Penugasan Guru', 'admin.penugasan-guru.index', 'tugas', ['admin.penugasan-guru.*']],
            ['Penempatan Siswa', 'admin.penempatan-siswa.index', 'pindah', ['admin.penempatan-siswa.*']],
        ],
        PeranPengguna::Guru => [
            ['Dashboard', 'dashboard', 'dashboard', ['dashboard']],
            ['Kelas Saya', 'guru.kelas.index', 'kelas', ['guru.kelas.*']],
            ['Materi', 'guru.materi.index', 'dokumen', ['guru.materi.*', 'guru.berkas.*']],
            ['Tugas & Penilaian', 'guru.tugas.index', 'tugas', ['guru.tugas.*', 'guru.pengumpulan-tugas.*', 'guru.penilaian.*']],
            ['Forum Diskusi', 'forum.index', 'forum', ['forum.*']],
            ['Sekar AI', 'chatbot.index', 'robot', ['chatbot.*']],
        ],
        default => [
            ['Dashboard', 'dashboard', 'dashboard', ['dashboard']],
            ['Mata Pelajaran', 'siswa.mata-pelajaran.index', 'buku', ['siswa.mata-pelajaran.*', 'siswa.materi.*']],
            ['Tugas & Nilai', 'siswa.nilai.index', 'tugas', ['siswa.nilai.*', 'siswa.tugas.*', 'siswa.pengumpulan-tugas.*']],
            ['Forum Diskusi', 'forum.index', 'forum', ['forum.*']],
            ['Sekar AI', 'chatbot.index', 'robot', ['chatbot.*']],
        ],
    };
@endphp

<div x-show="menu" x-cloak x-transition.opacity class="fixed inset-0 z-30 bg-navy-900/50 lg:hidden" @click="menu = false"></div>

<aside class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-navy text-white transition-transform duration-200 lg:translate-x-0"
       :class="{ 'translate-x-0': menu }" aria-label="Navigasi utama">
    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-5 py-6">
        <img src="{{ asset('images/logo.png') }}" alt="" class="size-11 shrink-0">
        <span class="leading-tight">
            <span class="block text-[15px] font-extrabold tracking-wide">SEKAR PERTIWI</span>
            <span class="block text-[11px] font-semibold tracking-wider text-gold">LEARNING PLATFORM</span>
        </span>
    </a>

    <div class="mx-5 border-t border-white/10"></div>

    <nav class="scroll-tipis flex-1 overflow-y-auto px-3 py-5">
        <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-white/50">Menu utama</p>
        <ul class="space-y-1">
            @foreach ($menu as [$label, $rute, $ikon, $pola])
                @php($aktif = request()->routeIs(...$pola))
                <li>
                    <a href="{{ route($rute) }}" @if ($aktif) aria-current="page" @endif
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ $aktif ? 'bg-gold text-navy' : 'text-white/85 hover:bg-white/10 hover:text-white' }}">
                        <x-ikon :nama="$ikon" class="size-[18px]" />
                        {{ $label }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    <div class="space-y-1 border-t border-white/10 px-3 py-4">
        @php($aktifProfil = request()->routeIs('profil.*'))
        <a href="{{ route('profil.edit') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ $aktifProfil ? 'bg-gold text-navy' : 'text-white/85 hover:bg-white/10 hover:text-white' }}">
            <x-ikon nama="kata-sandi" class="size-[18px]" /> Profil & password
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" data-tetap class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-white/85 transition hover:bg-white/10 hover:text-white">
                <x-ikon nama="keluar" class="size-[18px]" /> Keluar
            </button>
        </form>
    </div>
</aside>
