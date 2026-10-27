<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Satu berkas jawaban per pengumpulan. */
class StorePengumpulanTugasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->adalahSiswa() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'berkas' => [
                'required',
                'file',
                'extensions:'.implode(',', config('lms.unggah.ekstensi_tugas')),
                'max:'.config('lms.unggah.tugas_kb'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'berkas.required' => 'Pilih berkas jawaban terlebih dahulu.',
            'berkas.extensions' => 'Jenis berkas tidak diterima. Gunakan: '.implode(', ', config('lms.unggah.ekstensi_tugas')).'.',
            'berkas.max' => 'Ukuran berkas maksimal '.round(config('lms.unggah.tugas_kb') / 1024).' MB.',
        ];
    }
}
