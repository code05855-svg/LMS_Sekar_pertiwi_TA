{{--
    Lencana status. Terima enum (StatusMateri, StatusIndeks, StatusPengumpulan,
    PeranPengguna) atau teks. Contoh: <x-badge-status :status="$materi->status" />
    Label dapat diganti lewat slot.
--}}
@props(['status'])

@php
    $nilai = $status instanceof \BackedEnum ? $status->value : (is_bool($status) ? ($status ? 'aktif' : 'nonaktif') : (string) $status);

    $peta = [
        'draf' => ['badge-muted', 'Draf'],
        'terbit' => ['badge-success', 'Terbit'],
        'menunggu' => ['badge-gold', 'Menunggu'],
        'diproses' => ['badge-info', 'Diproses'],
        'terindeks' => ['badge-success', 'Terindeks'],
        'gagal' => ['badge-danger', 'Gagal'],
        'tepat_waktu' => ['badge-success', 'Tepat waktu'],
        'terlambat' => ['badge-danger', 'Terlambat'],
        'admin' => ['badge-danger', 'Admin'],
        'guru' => ['badge-gold', 'Guru'],
        'siswa' => ['badge-info', 'Siswa'],
        'aktif' => ['badge-success', 'Aktif'],
        'nonaktif' => ['badge-muted', 'Nonaktif'],
        'belum' => ['badge-danger', 'Belum dikumpulkan'],
        'dikumpulkan' => ['badge-info', 'Dikumpulkan'],
        'dinilai' => ['badge-success', 'Dinilai'],
        'berhasil' => ['badge-success', 'Berhasil'],
    ];

    [$kelas, $label] = $peta[$nilai] ?? ['badge-muted', ucfirst(str_replace('_', ' ', $nilai))];
@endphp

<span {{ $attributes->merge(['class' => $kelas]) }}>{{ $slot->isEmpty() ? $label : $slot }}</span>
