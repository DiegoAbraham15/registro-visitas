<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * 'cafeteria_cortesias' no tiene columna de fecha: es el estado "vigente" de
 * la habitación, no un historial por día, así que el desayuno/cena de ayer
 * se queda guardado indefinidamente hasta que alguien lo vuelve a tocar. Sin
 * este chequeo, una habitación con el mismo paciente dos días seguidos
 * mostraría el menú de ayer como si ya estuviera decidido hoy. Como
 * ComidaController::update() siempre reescribe desayuno+comida+cena+bebida
 * juntos, un solo 'updated_at' por fila alcanza para saber si esa decisión
 * es de hoy o quedó vieja — no hace falta una columna de fecha aparte.
 */
class CortesiaVigente
{
    public static function esDeHoy(?object $cortesia): bool
    {
        return $cortesia !== null
            && ! empty($cortesia->updated_at)
            && Carbon::parse($cortesia->updated_at)->isToday();
    }

    public static function tieneMenuDeHoy(?object $cortesia): bool
    {
        return self::esDeHoy($cortesia)
            && ! empty($cortesia->platillo_desayuno)
            && ! empty($cortesia->platillo_cena);
    }
}
