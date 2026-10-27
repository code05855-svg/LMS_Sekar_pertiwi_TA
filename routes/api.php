<?php

use App\Http\Controllers\Api\N8n\BagianMateriController;
use App\Http\Controllers\Api\N8n\BerkasMateriController;
use App\Http\Controllers\Api\N8n\FaqController;
use App\Http\Controllers\Api\N8n\PencarianMateriController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API untuk n8n  (/api/n8n/*)
|--------------------------------------------------------------------------
|
| Hanya dipanggil oleh workflow n8n, dilindungi satu token di .env
| (N8N_API_TOKEN) lewat middleware AuthenticateN8nToken.
| Setiap permintaan wajib membawa header "Accept: application/json".
*/

Route::middleware('n8n.token')->prefix('n8n')->name('api.n8n.')->group(function () {
    // Workflow 1 (Indeks Dokumen)
    Route::get('berkas-materi/{berkas}', [BerkasMateriController::class, 'show'])->name('berkas.show');
    Route::post('berkas-materi/{berkas}/bagian', [BagianMateriController::class, 'store'])->name('bagian.store');
    Route::delete('berkas-materi/{berkas}/bagian', [BagianMateriController::class, 'destroy'])->name('bagian.destroy');

    // Workflow 2 (Tanya Jawab)
    Route::get('pencarian-materi', [PencarianMateriController::class, 'index'])->name('pencarian.index');
    Route::get('faq', [FaqController::class, 'index'])->name('faq.index');
});
