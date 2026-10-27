<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Batas unggah berkas
    |--------------------------------------------------------------------------
    |
    | Satuan kilobyte. Pastikan upload_max_filesize dan post_max_size di
    | php.ini lebih besar dari angka ini, jika tidak PHP menolak berkas
    | sebelum Laravel sempat memeriksanya.
    */
    'unggah' => [
        'materi_kb' => (int) env('BATAS_UNGGAH_MATERI_KB', 20480),
        'tugas_kb' => (int) env('BATAS_UNGGAH_TUGAS_KB', 10240),

        // Jenis berkas jawaban tugas yang diterima siswa.
        'ekstensi_tugas' => ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'jpg', 'jpeg', 'png', 'zip'],
    ],

    // Pengembangan: lempar galat bila ada lazy loading (N+1). Matikan di produksi.
    'cegah_lazy_loading' => (bool) env('LMS_CEGAH_LAZY_LOADING', false),

    /*
    |--------------------------------------------------------------------------
    | Chatbot
    |--------------------------------------------------------------------------
    */
    'chatbot' => [
        'pertanyaan_min' => 3,
        'pertanyaan_maks' => 500,   // sama dengan pemotongan di node "Susun Prompt" n8n
        'batas_per_menit' => 10,    // throttle route chatbot.kirim
    ],

];
