<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    /**
     * FAQ dasar penggunaan LMS (sama dengan bagian 4 database_lms_sekolah.sql).
     * Aman dijalankan berulang: dicocokkan berdasarkan teks pertanyaan.
     */
    public function run(): void
    {
        $daftar = [
            [
                'Bagaimana cara login ke LMS?',
                'Buka halaman login, lalu masukkan nomor induk (NIS untuk siswa, NIP untuk guru) dan password yang diberikan admin sekolah.',
                'login masuk akun nomor induk nis nip password',
            ],
            [
                'Bagaimana jika saya lupa password?',
                'Hubungi admin sekolah untuk mengatur ulang password. Setelah berhasil login, password dapat diganti lewat menu Profil.',
                'lupa password ganti reset profil',
            ],
            [
                'Bagaimana cara melihat materi pelajaran?',
                'Buka Dashboard, pilih mata pelajaran, lalu pilih materi yang ingin dibaca. Berkas PDF pada materi dapat dibuka dan diunduh.',
                'materi pelajaran unduh berkas pdf',
            ],
            [
                'Bagaimana cara mengumpulkan tugas?',
                'Buka mata pelajaran, pilih tugas, lalu unggah berkas jawaban sebelum tenggat. Pengumpulan setelah tenggat berstatus terlambat.',
                'kumpul tugas unggah jawaban tenggat terlambat',
            ],
            [
                'Di mana saya melihat nilai tugas?',
                'Menu Nilai menampilkan status pengumpulan, nilai, dan komentar guru untuk setiap tugas.',
                'nilai tugas komentar status pengumpulan',
            ],
            [
                'Bagaimana cara memakai chatbot akademik?',
                'Buka menu Chatbot, ketik pertanyaan tentang materi pelajaran, lalu baca jawaban beserta sumber materinya. Jawaban berasal dari dokumen yang diunggah guru.',
                'chatbot tanya jawab sumber materi',
            ],
            [
                'Mengapa chatbot tidak menemukan jawaban?',
                'Kemungkinan materi belum diunggah, masih berstatus draf, atau belum selesai diindeks. Tanyakan kepada guru mata pelajaran terkait.',
                'chatbot tidak menemukan jawaban indeks draf',
            ],
            [
                'Bagaimana cara ikut forum diskusi?',
                'Buka mata pelajaran, pilih Forum, lalu buat topik baru atau balas topik yang ada. Topik yang dikunci guru tidak dapat dibalas.',
                'forum diskusi topik balasan kunci',
            ],
            [
                'Siapa yang mengatur akun dan kelas saya?',
                'Akun, kelas, dan mata pelajaran diatur oleh admin sekolah. Hubungi admin jika ada data yang perlu diperbaiki.',
                'admin akun kelas data penempatan',
            ],
        ];

        foreach ($daftar as [$pertanyaan, $jawaban, $kataKunci]) {
            Faq::updateOrCreate(
                ['pertanyaan' => $pertanyaan],
                ['jawaban' => $jawaban, 'kata_kunci' => $kataKunci, 'aktif' => true],
            );
        }
    }
}
