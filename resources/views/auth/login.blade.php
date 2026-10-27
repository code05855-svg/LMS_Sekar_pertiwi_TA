@extends('layouts.guest')

@section('judul', 'Masuk')

@section('konten')
<div class="grid min-h-screen lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]">
    {{-- Sisi kiri: foto sekolah --}}
    <aside class="relative hidden overflow-hidden text-white lg:block">
        <img src="{{ asset('images/login-sekolah.jpg') }}" alt="Gedung SMP Sekar Pertiwi" class="absolute inset-0 size-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-b from-navy-900/90 via-navy/70 to-navy-900/85"></div>

        <div class="relative flex h-full flex-col justify-between p-10 xl:p-14">
            <div>
                <img src="{{ asset('images/logo.png') }}" alt="Lambang SMP Sekar Pertiwi" class="size-24 drop-shadow-lg">
                <h1 class="mt-6 text-4xl font-extrabold tracking-tight">SMP SEKAR PERTIWI</h1>
                <p class="mt-3 text-2xl font-bold text-gold">Belajar, bertumbuh, berprestasi.</p>
                <p class="mt-4 max-w-md text-base leading-relaxed text-white/90">
                    Satu ruang digital untuk materi, tugas, diskusi, dan perjalanan akademikmu.
                </p>
            </div>

            <div>
                <div class="mb-5 h-px w-full bg-white/25"></div>
                <p class="text-xs font-bold uppercase tracking-widest text-gold">Belajar lebih terarah</p>
                <p class="mt-2 max-w-md text-sm leading-relaxed text-white/90">
                    Akses materi kapan saja. Pantau progres. Terhubung dengan guru dan teman. Tanya Sekar AI bila ada materi yang belum dipahami.
                </p>
                <p class="mt-8 text-xs text-white/60">© {{ date('Y') }} SMP Sekar Pertiwi, Kabupaten Bandung</p>
            </div>
        </div>
    </aside>

    {{-- Sisi kanan: formulir --}}
    <main class="flex items-center justify-center px-6 py-12 sm:px-12">
        <div class="w-full max-w-md">
            <img src="{{ asset('images/logo.png') }}" alt="" class="mb-6 size-16 lg:hidden">

            <h2 class="text-3xl font-bold tracking-tight text-ink">Selamat datang kembali</h2>
            <p class="mt-2 text-muted">Masuk ke akun untuk melanjutkan pembelajaran.</p>

            @if (session('sukses'))
                <x-alert tipe="sukses" class="mt-6">{{ session('sukses') }}</x-alert>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-5" x-data="{ lihat: false }">
                @csrf

                <x-field name="nomor_induk" label="Nomor induk (NIS / NIP) atau email" :value="old('nomor_induk')"
                         placeholder="Masukkan NIS, NIP, atau email" autocomplete="username" autofocus required />

                <div>
                    <label for="f-password" class="label">Kata sandi</label>
                    <div class="relative">
                        <input id="f-password" name="password" :type="lihat ? 'text' : 'password'" autocomplete="current-password" required
                               placeholder="Masukkan kata sandi" class="input pr-12 @error('password') input-error @enderror">
                        <button type="button" data-tetap @click="lihat = !lihat" class="btn-icon absolute right-1 top-1/2 -translate-y-1/2" :aria-label="lihat ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">
                            <x-ikon nama="lihat" class="size-5" />
                        </button>
                    </div>
                    @error('password') <p class="error-text">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-center gap-2 text-sm text-muted">
                    <input type="checkbox" name="ingat" value="1" class="size-4 rounded border-line text-navy focus:ring-navy"> Ingat saya di perangkat ini
                </label>

                <button type="submit" data-memuat="Memeriksa..." class="btn-primary w-full py-3 text-[15px]">Masuk ke akun</button>
            </form>

            <p class="mt-6 text-sm text-muted">Akses sesuai peran akun: Admin, Guru, atau Siswa.</p>

            <div class="mt-6 rounded-2xl bg-canvas p-5">
                <p class="text-sm font-semibold text-ink">Butuh bantuan masuk?</p>
                <p class="mt-1 text-xs text-muted">Lupa kata sandi atau akun belum aktif? Hubungi administrator sekolah untuk bantuan akun.</p>
            </div>
        </div>
    </main>
</div>
@endsection
