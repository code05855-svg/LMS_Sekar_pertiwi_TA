<?php
 
namespace App\Http\Requests;
 
use App\Enums\JenisJawaban;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
 
class StoreTugasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->adalahGuru() ?? false;
    }
 
    protected function prepareForValidation(): void
    {
        // Baris tautan yang kosong dibuang; selain array diabaikan.
        $tautan = $this->input('lampiran_tautan');
        $this->merge([
            'lampiran_tautan' => is_array($tautan)
                ? array_values(array_filter(array_map(fn ($t) => is_string($t) ? trim($t) : null, $tautan)))
                : [],
        ]);
    }
 
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $ekstensi = array_merge(config('lms.unggah.ekstensi_dokumen'), config('lms.unggah.ekstensi_foto'));
        $maks = (int) config('lms.unggah.lampiran_maks');
 
        $rules = [
            'judul' => ['required', 'string', 'max:200'],
            'instruksi' => ['nullable', 'string', 'max:10000'],
            'tenggat' => ['required', 'date'],
            'nilai_maksimum' => ['required', 'integer', 'between:1,1000'],
 
            // jenis jawaban yang diterima
            'jenis_jawaban' => ['required', 'array', 'min:1'],
            'jenis_jawaban.*' => ['string', Rule::in(JenisJawaban::nilaiSemua())],
 
            // lampiran dari guru: berkas (dokumen/foto) dan tautan
            'lampiran_berkas' => ['nullable', 'array', "max:{$maks}"],
            'lampiran_berkas.*' => ['file', 'extensions:'.implode(',', $ekstensi), 'max:'.config('lms.unggah.tugas_kb'), $this->fotoHarusGambar()],
            'lampiran_tautan' => ['array', "max:{$maks}"],
            'lampiran_tautan.*' => ['string', 'url:http,https', 'max:500'],
            'hapus_lampiran' => ['nullable', 'array'],
            'hapus_lampiran.*' => ['integer'],
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
 
    /** Berkas berekstensi gambar harus benar-benar gambar (isinya diperiksa, bukan hanya namanya). */
    private function fotoHarusGambar(): \Closure
    {
        return function (string $atribut, mixed $berkas, \Closure $gagal) {
            $ekstensiFoto = config('lms.unggah.ekstensi_foto');
 
            if ($berkas instanceof \Illuminate\Http\UploadedFile
                && in_array(strtolower($berkas->getClientOriginalExtension()), $ekstensiFoto, true)
                && @getimagesize($berkas->getRealPath()) === false) {
                $gagal('Berkas foto tidak valid atau rusak.');
            }
        };
    }
 
    public function messages(): array
    {
        return [
            'penugasan_guru_id.exists' => 'Kelas dan mata pelajaran yang dipilih bukan yang Anda ampu.',
            'jenis_jawaban.required' => 'Pilih minimal satu jenis jawaban yang diterima.',
            'jenis_jawaban.min' => 'Pilih minimal satu jenis jawaban yang diterima.',
            'lampiran_berkas.max' => 'Lampiran berkas maksimal :max per kali simpan.',
            'lampiran_berkas.*.extensions' => 'Jenis berkas lampiran tidak diterima.',
            'lampiran_berkas.*.max' => 'Ukuran tiap lampiran maksimal '.round(config('lms.unggah.tugas_kb') / 1024).' MB.',
            'lampiran_tautan.max' => 'Tautan lampiran maksimal :max per kali simpan.',
            'lampiran_tautan.*.url' => 'Tautan lampiran harus berupa alamat lengkap yang diawali http:// atau https://.',
        ];
    }
}
