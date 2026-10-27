<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Nilai 0 sampai nilai_maksimum tugas; komentar opsional. */
class UpdateNilaiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->adalahGuru() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $maksimum = $this->route('pengumpulan')->tugas->nilai_maksimum;

        return [
            'nilai' => ['required', 'numeric', 'min:0', 'max:'.$maksimum],
            'komentar' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'nilai.max' => 'Nilai tidak boleh lebih dari nilai maksimum tugas (:max).',
        ];
    }
}
