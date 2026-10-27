<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KirimPertanyaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->punyaPeran(
            \App\Enums\PeranPengguna::Siswa,
            \App\Enums\PeranPengguna::Guru,
        ) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'pertanyaan' => trim((string) preg_replace('/\s+/u', ' ', is_string($this->input('pertanyaan')) ? $this->input('pertanyaan') : '')),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'pertanyaan' => [
                'required',
                'string',
                'min:'.config('lms.chatbot.pertanyaan_min'),
                'max:'.config('lms.chatbot.pertanyaan_maks'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'pertanyaan.required' => 'Tulis pertanyaanmu dulu ya.',
            'pertanyaan.min' => 'Pertanyaan terlalu pendek (minimal :min huruf).',
            'pertanyaan.max' => 'Pertanyaan terlalu panjang (maksimal :max huruf).',
        ];
    }
}
