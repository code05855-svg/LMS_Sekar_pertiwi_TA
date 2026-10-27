<?php

namespace App\Http\Requests;

use App\Enums\StatusMateri;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Membuat dan mengubah materi. Pada pembuatan, penugasan harus milik guru
 * yang sedang login. Berkas PDF boleh dilampirkan sekaligus (berkas[]).
 */
class StoreMateriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->adalahGuru() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = [
            'judul' => ['required', 'string', 'max:200'],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(StatusMateri::class)],
            'berkas' => ['nullable', 'array', 'max:10'],
            'berkas.*' => StoreBerkasMateriRequest::aturanPdf(),
        ];

        // penugasan hanya dipilih saat membuat; materi yang sudah ada tidak dipindah
        if (! $this->route('materi')) {
            $rules['penugasan_guru_id'] = [
                'required',
                'integer',
                Rule::exists('penugasan_guru', 'id')->where('guru_id', $this->user()->id),
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'penugasan_guru_id.exists' => 'Kelas dan mata pelajaran yang dipilih bukan yang Anda ampu.',
            'berkas.*.mimes' => 'Berkas harus berformat PDF.',
            'berkas.*.mimetypes' => 'Berkas harus berformat PDF.',
        ];
    }
}
