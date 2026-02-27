<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use App\Models\Ph;
use App\Models\Inmueble;
use App\Models\Asistente;
use App\Models\Convocatoria;
use App\Models\OrdenDiaItem;
use App\Models\Reunion;
use App\Models\Timer;
use App\Models\ZonaComun;
use App\Models\Voto;
use App\Models\Opcion;
use App\Models\Pregunta;
use App\Policies\ConvocatoriaPolicy;
use App\Policies\OrdenDiaItemPolicy;
use App\Policies\ReunionPolicy;
use App\Policies\TimerPolicy;
use App\Policies\ZonaComunPolicy;
use App\Policies\OpcionPolicy;
use App\Policies\PreguntaPolicy;
use App\Policies\PhPolicy;
use App\Policies\InmueblePolicy;
use App\Policies\AsistentePolicy;
use App\Policies\VotoPolicy;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $registrosLimit = (int) config('app.registros_rate_limit', 1200);

        RateLimiter::for('registros', function (Request $request) use ($registrosLimit) {
            return Limit::perMinute($registrosLimit)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('webhooks', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        Broadcast::routes(['middleware' => ['auth:sanctum']]);

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Ph::class, PhPolicy::class);
        Gate::policy(Inmueble::class, InmueblePolicy::class);
        Gate::policy(Asistente::class, AsistentePolicy::class);
        Gate::policy(Reunion::class, ReunionPolicy::class);
        Gate::policy(Pregunta::class, PreguntaPolicy::class);
        Gate::policy(Opcion::class, OpcionPolicy::class);
        Gate::policy(Timer::class, TimerPolicy::class);
        Gate::policy(ZonaComun::class, ZonaComunPolicy::class);
        Gate::policy(OrdenDiaItem::class, OrdenDiaItemPolicy::class);
        Gate::policy(Convocatoria::class, ConvocatoriaPolicy::class);
        Gate::policy(Voto::class, VotoPolicy::class);
    }
}
