<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Unggah satu atau beberapa PDF pada materi. Hanya PDF (sumber chatbot),
 * dengan batas ukuran dari config/lms.php.
 */
class StoreBerkasMateriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->adalahGuru() ?? false;
    }

    /** Aturan satu berkas PDF; dipakai juga oleh StoreMateriRequest dan penggantian berkas. */
    public static function aturanPdf(): array
    {
        return [
            'file',
            'mimes:pdf',
            'mimetypes:application/pdf',
            'max:'.config('lms.unggah.materi_kb'),
        ];
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'berkas' => ['required', 'array', 'min:1', 'max:10'],
            'berkas.*' => self::aturanPdf(),
        ];
    }

    public function messages(): array
    {
        return [
            'berkas.required' => 'Pilih minimal satu berkas PDF.',
            'berkas.*.mimes' => 'Berkas harus berformat PDF.',
            'berkas.*.mimetypes' => 'Berkas harus berformat PDF.',
            'berkas.*.max' => 'Ukuran berkas maksimal '.round(config('lms.unggah.materi_kb') / 1024).' MB.',
        ];
    }
}
