<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTugasRequest extends FormRequest
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
            'instruksi' => ['nullable', 'string', 'max:10000'],
            'tenggat' => ['required', 'date'],
            'nilai_maksimum' => ['required', 'integer', 'between:1,1000'],
        ];

        if (! $this->route('tugas')) {
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
        ];
    }
}
