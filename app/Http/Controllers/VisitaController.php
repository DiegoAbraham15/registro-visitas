<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class VisitaController extends Controller
{
    /**
     * Columna del detalle propio de cada tabla hija, según el tipo de visitante.
     */
    private const CAMPO_DETALLE = [
        'familiar' => 'habitacion',
        'proveedor' => 'area_destino',
        'postulante' => 'puesto',
        'rep-medico' => 'consultorio',
    ];

    /**
     * Tipos de visitante cuyo detalle se captura con un <select> en cascada Piso → Valor,
     * validado contra un catálogo. 'postulante' queda fuera: su 'puesto' es texto libre.
     * 'campo_piso' es la columna real de la tabla hija (para guardar); 'piso_alias' es el
     * nombre con el que se selecciona esa misma columna en buscarVisita() (para leerla sin
     * chocar con la columna 'piso' de otras tablas hijas en el mismo SELECT).
     */
    private const CASCADA_POR_TIPO = [
        'proveedor' => ['catalogo' => 'catalogo_areas', 'columna' => 'nombre', 'campo_piso' => 'piso_destino', 'piso_alias' => 'piso_destino'],
        'familiar' => ['catalogo' => 'catalogo_habitaciones', 'columna' => 'numero', 'campo_piso' => 'piso', 'piso_alias' => 'piso_familiar'],
        'rep-medico' => ['catalogo' => 'catalogo_consultorios', 'columna' => 'numero', 'campo_piso' => 'piso', 'piso_alias' => 'piso_torre'],
    ];

    private function tablaHija(string $tipo): string
    {
        return match ($tipo) {
            'familiar' => 'visita_familiar',
            'proveedor' => 'visita_proveedor',
            'postulante' => 'visita_postulante',
            'rep-medico' => 'visita_torre',
            default => abort(422, 'Esta visita no tiene datos en ninguna tabla; no se puede editar.'),
        };
    }

    /**
     * El campo 'visita.tipo_visitante' no es confiable: el sistema real lo deja en un
     * valor genérico ("visitante") y el tipo real solo se puede saber por el edificio
     * y por cuál tabla hija tiene realmente los datos de esa visita. 'rep-medico' se
     * fuerza para todo id_edificio=2 SIN mirar las demás tablas, para blindarnos de
     * filas huérfanas o duplicadas que puedan existir por error en otras tablas hijas.
     */
    private const SQL_TIPO_REAL = "
        CASE
            WHEN visita.id_edificio = 2 THEN 'rep-medico'
            WHEN visita_postulante.puesto IS NOT NULL THEN 'postulante'
            WHEN visita_proveedor.area_destino IS NOT NULL THEN 'proveedor'
            WHEN visita_familiar.habitacion IS NOT NULL THEN 'familiar'
            ELSE 'sin-datos'
        END
    ";

    private function buscarVisita(int $id)
    {
        $visita = DB::table('visita')
            ->leftJoin('visita_familiar', 'visita.id_visita', '=', 'visita_familiar.id_visita')
            ->leftJoin('visita_proveedor', 'visita.id_visita', '=', 'visita_proveedor.id_visita')
            ->leftJoin('visita_postulante', 'visita.id_visita', '=', 'visita_postulante.id_visita')
            ->leftJoin('visita_torre', 'visita.id_visita', '=', 'visita_torre.id_visita')
            ->select(
                'visita.id_visita', 'visita.fecha_entrada', 'visita.fecha_salida', 'visita.estado', 'visita.id_edificio',
                DB::raw(self::SQL_TIPO_REAL . ' AS tipo_real'),
                DB::raw("CASE WHEN visita.id_edificio = 2 THEN visita_torre.nombre ELSE COALESCE(visita_familiar.nombre, visita_proveedor.nombre, visita_postulante.nombre) END AS nombre_visitante"),
                DB::raw("CASE WHEN visita.id_edificio = 2 THEN visita_torre.folio ELSE COALESCE(visita_familiar.folio, visita_proveedor.folio, visita_postulante.folio) END AS folio"),
                DB::raw("CASE WHEN visita.id_edificio = 2 THEN visita_torre.foto_persona ELSE COALESCE(visita_familiar.foto_persona, visita_proveedor.foto_persona, visita_postulante.foto_persona) END AS foto_persona"),
                DB::raw("CASE WHEN visita.id_edificio = 2 THEN visita_torre.consultorio ELSE COALESCE(visita_postulante.puesto, visita_proveedor.area_destino, visita_familiar.habitacion) END AS detalle"),
                'visita_proveedor.area_destino',
                'visita_familiar.piso as piso_familiar', 'visita_proveedor.piso_destino', 'visita_torre.piso as piso_torre'
            )
            ->where('visita.id_visita', $id)
            ->first();

        if (!$visita) {
            abort(404);
        }

        return $visita;
    }

    private function autorizar($visita): void
    {
        $usuario = Auth::user();

        if ($usuario->es_admin) {
            return;
        }

        $permitido = match ($usuario->area) {
            'hospital' => (int) $visita->id_edificio === 1,
            'consultorios' => (int) $visita->id_edificio === 2,
            'cafeteria' => $visita->tipo_real === 'proveedor' && $visita->area_destino === 'CAFETERÍA',
            default => false,
        };

        if (!$permitido) {
            abort(403, 'No tienes permisos para modificar este registro.');
        }
    }

    private function urlDashboard(): string
    {
        return '/' . Auth::user()->area . '/dashboard';
    }

    /**
     * Catálogo genérico piso → valores (áreas u habitaciones) para el <select> en cascada.
     * Se preserva el orden de piso_orden porque los arrays asociativos de PHP mantienen
     * el orden de inserción.
     */
    private function catalogoPorPiso(string $tabla, string $columna): array
    {
        $porPiso = [];

        DB::table($tabla)
            ->orderBy('piso_orden')
            ->orderBy($columna)
            ->get(['piso', $columna])
            ->each(function ($fila) use (&$porPiso, $columna) {
                $porPiso[$fila->piso][] = $fila->$columna;
            });

        return $porPiso;
    }

    public function edit(int $id)
    {
        $visita = $this->buscarVisita($id);
        $this->autorizar($visita);

        $config = self::CASCADA_POR_TIPO[$visita->tipo_real] ?? null;
        $opcionesPorPiso = $config ? $this->catalogoPorPiso($config['catalogo'], $config['columna']) : [];
        $pisoActual = $config ? $visita->{$config['piso_alias']} : null;

        return view('visitas.editar', compact('visita', 'opcionesPorPiso', 'pisoActual'));
    }

    public function update(Request $request, int $id)
    {
        $visita = $this->buscarVisita($id);
        $this->autorizar($visita);

        $config = self::CASCADA_POR_TIPO[$visita->tipo_real] ?? null;

        $reglas = [
            'nombre_visitante' => ['required', 'string', 'max:150'],
            'detalle' => ['nullable', 'string', 'max:100'],
            'estado' => ['required', 'in:activa,finalizada'],
        ];

        if ($config) {
            $reglas['piso'] = ['required', 'string', 'max:30'];
            $reglas['detalle'] = [
                'required',
                'string',
                'max:100',
                Rule::exists($config['catalogo'], $config['columna'])->where('piso', $request->input('piso')),
            ];
        }

        $validated = $request->validate($reglas);

        $tabla = $this->tablaHija($visita->tipo_real);

        if ($config) {
            $datosHija = [
                'nombre' => $validated['nombre_visitante'],
                $config['campo_piso'] => $validated['piso'],
                self::CAMPO_DETALLE[$visita->tipo_real] => $validated['detalle'],
            ];
        } else {
            $datosHija = [
                'nombre' => $validated['nombre_visitante'],
                self::CAMPO_DETALLE[$visita->tipo_real] => $validated['detalle'],
            ];
        }

        DB::table($tabla)->where('id_visita', $id)->update($datosHija);

        $datosVisita = ['estado' => $validated['estado']];

        if ($validated['estado'] === 'finalizada' && $visita->estado !== 'finalizada') {
            // Se acaba de marcar la salida: registra el momento exacto.
            $datosVisita['fecha_salida'] = now();
        } elseif ($validated['estado'] === 'activa' && $visita->estado === 'finalizada') {
            // Se reabrió la visita: ya no tiene una salida registrada.
            $datosVisita['fecha_salida'] = null;
        }

        DB::table('visita')->where('id_visita', $id)->update($datosVisita);

        return redirect($this->urlDashboard())->with('status', 'Registro actualizado correctamente.');
    }

    public function destroy(int $id)
    {
        $visita = $this->buscarVisita($id);
        $this->autorizar($visita);

        // Un registro 'sin-datos' no tiene tabla hija que borrar (es un huérfano de origen);
        // aun así se debe poder limpiar la fila de 'visita'.
        $tabla = $visita->tipo_real !== 'sin-datos' ? $this->tablaHija($visita->tipo_real) : null;

        DB::transaction(function () use ($tabla, $id) {
            if ($tabla) {
                DB::table($tabla)->where('id_visita', $id)->delete();
            }
            DB::table('visita')->where('id_visita', $id)->delete();
        });

        return redirect($this->urlDashboard())->with('status', 'Registro eliminado correctamente.');
    }
}
