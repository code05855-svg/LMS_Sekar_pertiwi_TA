<?php

namespace App\Http\Requests;

use App\Enums\PeranPengguna;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAkunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function peran(): PeranPengguna
    {
        return $this->routeIs('admin.siswa.*') ? PeranPengguna::Siswa : PeranPengguna::Guru;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $id = $this->route('akun')?->id;

        return [
            'nomor_induk' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users', 'nomor_induk')->ignore($id)],
            'nama' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:100', Rule::unique('users', 'email')->ignore($id)],
            'password' => ['nullable', 'string', 'min:8', 'max:100'],
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

    /** Password kosong berarti tidak diubah. */
    public function dataAkun(): array
    {
        $data = $this->validated();
        $data['aktif'] = $this->boolean('aktif');
        $data['email'] = $data['email'] ?? null;

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        if ($this->peran() === PeranPengguna::Guru) {
            $data['kelas_id'] = null;
        } else {
            $data['kelas_id'] = $data['kelas_id'] ?? null;
        }

        return $data;
    }
}
