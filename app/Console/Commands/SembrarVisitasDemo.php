<?php

namespace App\Console\Commands;

use Database\Seeders\VisitasDemoSeeder;
use Illuminate\Console\Command;

/**
 * Comando para agregar visitas de prueba en la cantidad que se pida, sin tener
 * que editar el seeder cada vez. Reutiliza VisitasDemoSeeder::generar(), que
 * ya continúa la numeración de folio DEMO-00XX donde se haya quedado.
 */
class SembrarVisitasDemo extends Command
{
    protected $signature = 'visitas:sembrar-demo {cantidad=100 : Cuántas visitas de prueba generar}';

    protected $description = 'Genera visitas de prueba (folio DEMO-00XX) repartidas entre los 4 tipos de visitante';

    public function handle(): int
    {
        $cantidad = (int) $this->argument('cantidad');

        if ($cantidad < 1) {
            $this->error('La cantidad debe ser mayor a 0.');

            return self::FAILURE;
        }

        (new VisitasDemoSeeder)->setCommand($this)->generar($cantidad);

        return self::SUCCESS;
    }
}
