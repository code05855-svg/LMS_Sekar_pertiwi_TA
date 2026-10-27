<?php

namespace App\Providers;

use App\Models\ForumBalasan;
use App\Models\ForumTopik;
use App\Models\Materi;
use App\Models\PengumpulanTugas;
use App\Models\Tugas;
use App\Policies\ForumBalasanPolicy;
use App\Policies\ForumTopikPolicy;
use App\Policies\MateriPolicy;
use App\Policies\PengumpulanTugasPolicy;
use App\Policies\TugasPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Tanggal relatif dan nama bulan/hari memakai bahasa aplikasi ("2 jam yang lalu", "Okt").
        Carbon::setLocale((string) config('app.locale'));

        // Kolom yang tidak ada di $fillable memunculkan galat yang jelas,
        // bukan hilang diam-diam (hanya di luar produksi).
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        // Opsional: deteksi N+1 saat pengembangan (LMS_CEGAH_LAZY_LOADING=true).
        Model::preventLazyLoading((bool) config('lms.cegah_lazy_loading'));

        Gate::policy(Materi::class, MateriPolicy::class);
        Gate::policy(Tugas::class, TugasPolicy::class);
        Gate::policy(PengumpulanTugas::class, PengumpulanTugasPolicy::class);
        Gate::policy(ForumTopik::class, ForumTopikPolicy::class);
        Gate::policy(ForumBalasan::class, ForumBalasanPolicy::class);

        Paginator::defaultView('vendor.pagination.lms');
        Paginator::defaultSimpleView('vendor.pagination.lms-sederhana');
    }
}
