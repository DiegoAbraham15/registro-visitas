<?php

namespace App\Http\Controllers;

use App\Models\MenuDia;
use App\Models\MenuSemanaOpciones;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    private function limpiarOpciones(?array $opciones): array
    {
        return collect($opciones ?? [])
            ->map(fn ($opcion) => trim($opcion ?? ''))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * La comida es fija pero distinta cada día (7 filas); el desayuno y la
     * cena, en cambio, se eligen entre las mismas opciones cualquier día de
     * la semana (una sola fila en 'menu_semana_opciones').
     */
    public function edit()
    {
        foreach (MenuDia::DIAS as $dia) {
            MenuDia::firstOrCreate(['dia' => $dia]);
        }

        $dias = MenuDia::whereIn('dia', MenuDia::DIAS)
            ->get()
            ->sortBy(fn ($menuDia) => array_search($menuDia->dia, MenuDia::DIAS))
            ->values();

        $opciones = MenuSemanaOpciones::actual();

        return view('menus.edit', compact('dias', 'opciones'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'dias' => ['required', 'array'],
            'dias.*.comida' => ['nullable', 'string', 'max:150'],
            'desayuno_opciones' => ['nullable', 'array'],
            'desayuno_opciones.*' => ['nullable', 'string', 'max:150'],
            'cena_opciones' => ['nullable', 'array'],
            'cena_opciones.*' => ['nullable', 'string', 'max:150'],
        ]);

        foreach (MenuDia::DIAS as $dia) {
            MenuDia::updateOrCreate(['dia' => $dia], [
                'comida' => $validated['dias'][$dia]['comida'] ?? null,
            ]);
        }

        MenuSemanaOpciones::actual()->update([
            'desayuno_opciones' => $this->limpiarOpciones($validated['desayuno_opciones'] ?? null),
            'cena_opciones' => $this->limpiarOpciones($validated['cena_opciones'] ?? null),
        ]);

        return redirect('/vinculacion/menus')->with('status', 'Menú semanal actualizado correctamente.');
    }
}
