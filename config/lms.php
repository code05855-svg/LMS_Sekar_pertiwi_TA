<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Batas unggah berkas
    | satuan kylobyte
    |--------------------------------------------------------------------------
    */
    'unggah' => [
        'materi_kb' => (int) env('BATAS_UNGGAH_MATERI_KB', 20480),
        'tugas_kb' => (int) env('BATAS_UNGGAH_TUGAS_KB', 10240),
        // jenis dokumen dan foto pada jawaban lampiran guru dan siswa
        'ekstensi_dokumen' => ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'zip'],
        'ekstensi_foto' => ['jpg', 'jpeg', 'png', 'webp'],

        // lampiran guru per tugas dan panjang jawaban teks siswa
        'lampiran_maks' => 5,
        'teks_jawaban_maks' => 20000,
        'foto_profil_kb' => (int) env('BATAS_FOTO_PROFIL_KB', 4096),
        'foto_profil_piksel_maks' => 6000,

        // 'ekstensi_tugas' => ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'jpg', 'jpeg', 'png', 'zip'],
    ],

    // Pengembangan: lempar galat bila ada lazy loading (N+1). Matikan di produksi.
    'cegah_lazy_loading' => (bool) env('LMS_CEGAH_LAZY_LOADING', false),

    /*
    | Chatbot
    */
    'chatbot' => [
        'pertanyaan_min' => 3,
        'pertanyaan_maks' => 500,   // sama dengan pemotongan di node "Susun Prompt" n8n
        'batas_per_menit' => 10,    // throttle route chatbot.kirim
        'retensi_hari' => (int) env('CHAT_RETENSI_HARI', 30),
        'bersih_otomatis' => (bool) env('CHAT_BERSIH_OTOMATIS', true),
    ],

];
