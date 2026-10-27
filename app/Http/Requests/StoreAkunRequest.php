<?php

namespace App\Http\Requests;

use App\Enums\PeranPengguna;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Menambah akun guru atau siswa (admin). Peran ditentukan dari route
 * (admin.guru.* atau admin.siswa.*), bukan dari input, supaya tidak bisa
 * disisipkan lewat formulir.
 */
class StoreAkunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dibatasi middleware peran:admin pada route
    }

    public function peran(): PeranPengguna
    {
        return $this->routeIs('admin.siswa.*') ? PeranPengguna::Siswa : PeranPengguna::Guru;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nomor_induk' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users', 'nomor_induk')],
            'nama' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:100', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'max:100'],
            'kelas_id' => [
                'nullable',
                $this->peran() === PeranPengguna::Siswa ? Rule::exists('kelas', 'id') : 'prohibited',
            ],
            'aktif' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nomor_induk.regex' => 'Nomor induk hanya boleh berisi huruf, angka, titik, strip, dan garis bawah.',
        ];
    }

    /** Data siap disimpan: peran dari route, aktif default true. */
    public function dataAkun(): array
    {
        $data = $this->validated();
        $data['peran'] = $this->peran();
        $data['aktif'] = $this->boolean('aktif', true);
        $data['email'] = $data['email'] ?? null;
        $data['kelas_id'] = $this->peran() === PeranPengguna::Siswa ? ($data['kelas_id'] ?? null) : null;

        return $data;
    }
}
