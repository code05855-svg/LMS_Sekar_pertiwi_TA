<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTopikForumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null; // hak akses forum diperiksa ForumTopikPolicy di controller
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'judul' => ['required', 'string', 'max:200'],
            'isi' => ['required', 'string', 'max:5000'],
        ];
    }
}
