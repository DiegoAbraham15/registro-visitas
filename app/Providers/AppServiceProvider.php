<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

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
        $this->configureDefaults();
        $this->configureHealthChecks();
        $this->configureRateLimiting();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Hace que '/up' realmente detecte problemas en vez de solo confirmar que la
     * app arrancó. Antes de esto, un incidente como el de la IP de LAN vieja
     * (DB_HOST/MOBILE_UPLOADS_URL apuntando a un host inalcanzable) hubiera
     * seguido devolviendo 200 en '/up' mientras la BD y las fotos fallaban.
     */
    protected function configureHealthChecks(): void
    {
        Event::listen(function (DiagnosingHealth $event): void {
            try {
                DB::connection()->getPdo();
            } catch (\Throwable $e) {
                throw new RuntimeException('No se pudo conectar a la base de datos: '.$e->getMessage(), previous: $e);
            }

            $urlFotos = config('app.mobile_uploads_url');

            if ($urlFotos) {
                $host = parse_url($urlFotos, PHP_URL_HOST);
                $puerto = parse_url($urlFotos, PHP_URL_PORT);

                if (! is_string($host) || $host === '') {
                    throw new RuntimeException("MOBILE_UPLOADS_URL no es una URL válida: {$urlFotos}");
                }

                $conexion = @fsockopen($host, is_int($puerto) ? $puerto : 80, $errno, $errstr, 3);

                if (! $conexion) {
                    throw new RuntimeException("No se pudo conectar al servidor de fotos de la app móvil ({$urlFotos}): {$errstr}");
                }

                fclose($conexion);
            }

            if (config('app.debug') && ! app()->environment('local')) {
                Log::warning('APP_DEBUG está activado fuera del entorno local: expone stack traces y detalles de conexión a BD si algo falla.');
            }
        });
    }

    /**
     * Antes vivía en App\Providers\FortifyServiceProvider (paquete ya removido —
     * el login real siempre fue el de routes/web.php, nunca el de Fortify).
     * Usado por el middleware 'throttle:login' en la ruta POST /login.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
