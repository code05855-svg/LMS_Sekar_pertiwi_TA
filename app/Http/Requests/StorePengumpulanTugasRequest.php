<?php
 
namespace App\Http\Requests;
 
use App\Enums\JenisJawaban;
use App\Models\Tugas;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
 
/**
 * Jawaban siswa: satu jawaban per tugas, berjenis dokumen, foto, tautan, atau teks.
 * Jenis yang boleh dipilih mengikuti tugas.jenis_jawaban.
 */
class StorePengumpulanTugasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->adalahSiswa() ?? false;
    }
 
    protected function prepareForValidation(): void
    {
        $this->merge([
            'tautan' => is_string($this->input('tautan')) ? trim($this->input('tautan')) : $this->input('tautan'),
            'teks' => is_string($this->input('teks')) ? trim($this->input('teks')) : $this->input('teks'),
        ]);
    }
 
    /** Tugas yang sedang dijawab (rute store membawa {tugas}, rute update membawa {pengumpulan}). */
    private function tugas(): Tugas
    {
        return $this->route('tugas') ?? $this->route('pengumpulan')->tugas;
    }
 
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $jenis = $this->input('jenis');
 
        $rules = [
            'jenis' => ['required', Rule::in($this->tugas()->jenis_jawaban)],
        ];
 
        $rules += match ($jenis) {
            JenisJawaban::Dokumen->value => ['berkas' => [
                'required', 'file',
                'extensions:'.implode(',', config('lms.unggah.ekstensi_dokumen')),
                'max:'.config('lms.unggah.tugas_kb'),
            ]],
            JenisJawaban::Foto->value => ['berkas' => [
                'required', 'file',
                'mimes:'.implode(',', config('lms.unggah.ekstensi_foto')),
                'max:'.config('lms.unggah.tugas_kb'),
            ]],
            JenisJawaban::Tautan->value => ['tautan' => ['required', 'string', 'url:http,https', 'max:500']],
            JenisJawaban::Teks->value => ['teks' => ['required', 'string', 'min:5', 'max:'.config('lms.unggah.teks_jawaban_maks')]],
            default => [],
        };
 
        return $rules;
    }
 
    public function messages(): array
    {
        return [
            'jenis.required' => 'Pilih jenis jawaban terlebih dahulu.',
            'jenis.in' => 'Jenis jawaban ini tidak diterima untuk tugas ini.',
            'berkas.required' => 'Pilih berkas jawaban terlebih dahulu.',
            'berkas.extensions' => 'Jenis dokumen tidak diterima. Gunakan: '.implode(', ', config('lms.unggah.ekstensi_dokumen')).'.',
            'berkas.mimes' => 'Foto harus berformat '.implode(', ', config('lms.unggah.ekstensi_foto')).'.',
            'berkas.max' => 'Ukuran berkas maksimal '.round(config('lms.unggah.tugas_kb') / 1024).' MB.',
            'tautan.required' => 'Isi tautan jawabanmu.',
            'tautan.url' => 'Tautan harus berupa alamat lengkap yang diawali http:// atau https://.',
            'tautan.max' => 'Tautan terlalu panjang (maksimal 500 karakter).',
            'teks.required' => 'Tulis jawabanmu terlebih dahulu.',
            'teks.min' => 'Jawaban teks terlalu pendek.',
            'teks.max' => 'Jawaban teks maksimal '.number_format((int) config('lms.unggah.teks_jawaban_maks'), 0, ',', '.').' karakter.',
        ];
    }
}
