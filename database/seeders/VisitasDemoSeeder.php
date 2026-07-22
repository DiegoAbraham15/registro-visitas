<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Genera visitas de prueba en la BD compartida con la app móvil.
 *
 * Uso directo: `php artisan db:seed --class=VisitasDemoSeeder` (100 visitas).
 * Uso con cantidad a elegir: `php artisan visitas:sembrar-demo {cantidad}`
 * (ver App\Console\Commands\SembrarVisitasDemo), que llama a generar().
 *
 * Todas las filas quedan marcadas con folio 'DEMO-00XX' (en las 4 tablas hija),
 * numeradas de forma consecutiva entre corridas (nunca reinicia en 0001 si ya
 * hay filas DEMO), para poder identificarlas y borrarlas después sin tocar
 * datos reales:
 *
 *   DELETE v, vf, vp, vpo, vt FROM visita v
 *   LEFT JOIN visita_familiar vf ON vf.id_visita = v.id_visita
 *   LEFT JOIN visita_proveedor vp ON vp.id_visita = v.id_visita
 *   LEFT JOIN visita_postulante vpo ON vpo.id_visita = v.id_visita
 *   LEFT JOIN visita_torre vt ON vt.id_visita = v.id_visita
 *   WHERE COALESCE(vf.folio, vp.folio, vpo.folio, vt.folio) LIKE 'DEMO-%';
 */
class VisitasDemoSeeder extends Seeder
{
    private const PREFIJO_FOLIO = 'DEMO';

    public function run(): void
    {
        $this->generar(100);
    }

    /**
     * Genera $cantidad visitas de prueba, repartidas parejo entre los 4 tipos
     * que el sistema reconoce (ver App\Support\VisitaConsultas), continuando
     * la numeración de folio DEMO-00XX donde se haya quedado la corrida anterior.
     */
    public function generar(int $cantidad): void
    {
        // fakerphp/faker no trae proveedor 'es_MX' en esta versión (cae en silencio a
        // nombres en inglés); 'es_ES' sí existe y da nombres realmente en español.
        $faker = Factory::create('es_ES');

        $areas = DB::table('catalogo_areas')->get();
        $habitaciones = DB::table('catalogo_habitaciones')->get();
        $consultorios = DB::table('catalogo_consultorios')->get();

        $tipos = $this->repartirTipos($cantidad)->shuffle();

        $numeroFolio = $this->siguienteNumeroFolio();
        $primerFolio = $numeroFolio;

        foreach ($tipos as $tipo) {
            $folio = self::PREFIJO_FOLIO.'-'.str_pad((string) $numeroFolio, 4, '0', STR_PAD_LEFT);
            $nombre = $faker->name();
            [$fechaEntrada, $fechaSalida, $estado] = $this->fechasAleatorias($faker);
            $idEdificio = $tipo === 'rep-medico' ? 2 : 1;

            $idVisita = DB::table('visita')->insertGetId([
                'id_edificio' => $idEdificio,
                // El sistema real deja este campo genérico; el tipo real se deduce
                // por la tabla hija (ver comentarios en VisitaConsultas).
                'tipo_visitante' => 'visitante',
                'fecha_entrada' => $fechaEntrada,
                'fecha_salida' => $fechaSalida,
                'estado' => $estado,
            ]);

            match ($tipo) {
                'familiar' => $this->crearFamiliar($idVisita, $faker, $habitaciones, $folio, $nombre),
                'proveedor' => $this->crearProveedor($idVisita, $faker, $areas, $folio, $nombre, $fechaEntrada, $estado),
                'postulante' => $this->crearPostulante($idVisita, $faker, $folio, $nombre),
                'rep-medico' => $this->crearTorre($idVisita, $faker, $consultorios, $folio, $nombre),
            };

            $numeroFolio++;
        }

        $primerFolioTexto = self::PREFIJO_FOLIO.'-'.str_pad((string) $primerFolio, 4, '0', STR_PAD_LEFT);
        $ultimoFolioTexto = self::PREFIJO_FOLIO.'-'.str_pad((string) ($numeroFolio - 1), 4, '0', STR_PAD_LEFT);

        $this->command?->info("{$cantidad} visitas de prueba creadas con folio {$primerFolioTexto} a {$ultimoFolioTexto}.");
    }

    /**
     * Reparte $cantidad entre los 4 tipos lo más parejo posible (el residuo de
     * la división, si lo hay, se reparte una unidad extra a los primeros tipos).
     */
    private function repartirTipos(int $cantidad): Collection
    {
        $tiposBase = ['familiar', 'proveedor', 'postulante', 'rep-medico'];
        $porTipo = intdiv($cantidad, count($tiposBase));
        $residuo = $cantidad % count($tiposBase);

        return collect($tiposBase)->flatMap(function ($tipo, $indice) use ($porTipo, $residuo) {
            $extra = $indice < $residuo ? 1 : 0;

            return array_fill(0, $porTipo + $extra, $tipo);
        });
    }

    /**
     * Siguiente número de folio DEMO-00XX a usar, continuando desde el máximo
     * que ya exista en cualquiera de las 4 tablas hija (1 si no hay ninguno).
     */
    private function siguienteNumeroFolio(): int
    {
        $maximo = collect(['visita_familiar', 'visita_proveedor', 'visita_postulante', 'visita_torre'])
            ->map(fn ($tabla) => DB::table($tabla)->where('folio', 'like', self::PREFIJO_FOLIO.'-%')->max('folio'))
            ->filter()
            ->map(fn ($folio) => (int) substr($folio, strlen(self::PREFIJO_FOLIO) + 1))
            ->max();

        return ($maximo ?? 0) + 1;
    }

    /**
     * Reparte las fechas en franjas (hoy / semana / mes / más viejo) para que
     * los filtros de periodo del reporte (dia/semana/mes/todo) tengan datos en
     * las cuatro vistas. Solo las visitas recientes pueden quedar 'activa'
     * (sin fecha_salida); las viejas siempre se generan como 'finalizada'.
     */
    private function fechasAleatorias($faker): array
    {
        $ahora = Carbon::now();
        $franja = $faker->randomElement(['hoy', 'hoy', 'semana', 'semana', 'mes', 'viejo']);

        $fechaEntrada = match ($franja) {
            'hoy' => $ahora->copy()->subMinutes($faker->numberBetween(5, 600)),
            'semana' => $ahora->copy()->subDays($faker->numberBetween(1, 6))->subMinutes($faker->numberBetween(0, 600)),
            'mes' => $ahora->copy()->subDays($faker->numberBetween(7, 28))->subMinutes($faker->numberBetween(0, 600)),
            'viejo' => $ahora->copy()->subDays($faker->numberBetween(29, 180))->subMinutes($faker->numberBetween(0, 600)),
        };

        $puedeQuedarActiva = $franja !== 'viejo' && $faker->boolean(25);

        if ($puedeQuedarActiva) {
            return [$fechaEntrada, null, 'activa'];
        }

        $fechaSalida = $fechaEntrada->copy()->addMinutes($faker->numberBetween(10, 240));
        if ($fechaSalida->isFuture()) {
            $fechaSalida = $ahora->copy();
        }

        return [$fechaEntrada, $fechaSalida, 'finalizada'];
    }

    private function crearFamiliar(int $idVisita, $faker, $habitaciones, string $folio, string $nombre): void
    {
        $habitacion = $habitaciones->random();

        DB::table('visita_familiar')->insert([
            'id_visita' => $idVisita,
            'nombre' => $nombre,
            'parentesco' => $faker->randomElement(['Hijo(a)', 'Esposo(a)', 'Padre', 'Madre', 'Hermano(a)', 'Otro familiar']),
            'habitacion' => $habitacion->numero,
            'piso' => $habitacion->piso,
            'nombre_paciente' => $faker->name(),
            'folio' => $folio,
            'foto_persona' => null,
            'foto_ine' => null,
        ]);
    }

    private function crearProveedor(int $idVisita, $faker, $areas, string $folio, string $nombre, Carbon $fechaEntrada, string $estado): void
    {
        $area = $areas->random();

        DB::table('visita_proveedor')->insert([
            'id_visita' => $idVisita,
            'empresa_representada' => $faker->company(),
            'nombre' => $nombre,
            'piso_destino' => $area->piso,
            'area_destino' => $area->nombre,
            'hora_entrada' => $fechaEntrada->format('H:i:s'),
            'hora_salida' => null,
            'estado' => $estado,
            'fecha' => $fechaEntrada->format('Y-m-d'),
            'folio' => $folio,
            'foto_persona' => null,
            'foto_ine' => null,
            'motivo_visita' => $faker->randomElement(['Entrega de mercancía', 'Mantenimiento', 'Revisión de equipo', 'Visita comercial', 'Entrega de documentos']),
        ]);
    }

    private function crearPostulante(int $idVisita, $faker, string $folio, string $nombre): void
    {
        DB::table('visita_postulante')->insert([
            'id_visita' => $idVisita,
            'nombre' => $nombre,
            'puesto' => $faker->randomElement([
                'Enfermero(a) General', 'Médico General', 'Auxiliar de Farmacia', 'Técnico en Radiología',
                'Camillero(a)', 'Recepcionista', 'Contador(a)', 'Auxiliar Administrativo',
                'Ingeniero(a) de Sistemas', 'Personal de Limpieza', 'Nutriólogo(a)', 'Trabajador(a) Social',
                'Químico(a) Clínico', 'Paramédico',
            ]),
            'area_destino' => $faker->randomElement(['Recursos Humanos', 'Sistemas', 'Enfermería', 'Administración', 'Farmacia']),
            'responsable_rh' => $faker->name(),
            'tipo_cita' => $faker->randomElement(['Entrevista inicial', 'Segunda entrevista', 'Entrevista final', 'Prueba psicométrica']),
            'cv_entregado' => $faker->boolean(80),
            'foto_persona' => null,
            'foto_ine' => null,
            'folio' => $folio,
        ]);
    }

    private function crearTorre(int $idVisita, $faker, $consultorios, string $folio, string $nombre): void
    {
        $consultorio = $consultorios->random();

        DB::table('visita_torre')->insert([
            'id_visita' => $idVisita,
            // Valores reales observados en la app móvil: 'visitante', 'paciente',
            // 'proveedor' (ver App\Support\VisitaConsultas — el tipo real de una
            // visita a Torre de Consultorios es este mismo campo).
            'tipo_acceso' => $faker->randomElement(['visitante', 'paciente', 'proveedor']),
            'piso' => $consultorio->piso,
            'consultorio' => $consultorio->numero,
            'nombre' => $nombre,
            'foto_persona' => null,
            'folio' => $folio,
        ]);
    }
}
