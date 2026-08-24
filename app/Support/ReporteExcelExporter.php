<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Genera el Excel (.xlsx real, no el .csv de siempre) del reporte de visitas
 * con un enlace a la foto de cada visitante y, para Torre de Consultorios, el
 * médico capturado en la visita.
 *
 * La foto se enlaza, no se embebe: las fotos las sirve el backend de la app
 * móvil (no este proyecto, ver MOBILE_UPLOADS_URL) y descargarlas una por una
 * al generar el archivo dejaba la petición colgada cuando ese servidor está
 * lento o no es alcanzable desde donde se genera el reporte. Un enlace es
 * instantáneo de generar y de cualquier forma abre la foto con un clic.
 */
class ReporteExcelExporter
{
    private const COLOR_ENLACE = '2757C9';

    public static function generar(array $datos): Spreadsheet
    {
        $etiquetaTipo = fn ($t) => match ($t) {
            'sin-datos' => 'Sin datos',
            'ex_empleado' => 'Ex empleado',
            default => ucfirst($t),
        };

        $filas = $datos['detalleVisitas'];

        $spreadsheet = new Spreadsheet;
        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setTitle('Detalle de visitas');

        $tituloPeriodo = $datos['etiquetasPeriodo'][$datos['periodo']] ?? $datos['periodo'];
        $hoja->setCellValue('A1', "Detalle de visitas ({$tituloPeriodo}) — {$filas->count()} registros");
        $hoja->mergeCells('A1:K1');
        $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $encabezados = ['Folio', 'Foto', 'Nombre', 'Tipo', 'Edificio', 'Médico', 'Detalle', 'Piso', 'Entrada', 'Salida', 'Estado'];
        $hoja->fromArray($encabezados, null, 'A2');
        $hoja->getStyle('A2:K2')->getFont()->setBold(true);
        $hoja->freezePane('A3');

        foreach (['A' => 12, 'B' => 10, 'C' => 26, 'D' => 14, 'E' => 20, 'F' => 26, 'G' => 22, 'H' => 12, 'I' => 17, 'J' => 17, 'K' => 12] as $columna => $ancho) {
            $hoja->getColumnDimension($columna)->setWidth($ancho);
        }

        $filaActual = 3;
        foreach ($filas as $dv) {
            $hoja->setCellValue("A{$filaActual}", $dv->folio ?? 'N/A');

            $urlFoto = self::urlFoto($dv->foto_persona ?? null);
            if ($urlFoto) {
                $hoja->setCellValue("B{$filaActual}", 'Ver foto');
                $hoja->getCell("B{$filaActual}")->getHyperlink()->setUrl($urlFoto);
                $hoja->getStyle("B{$filaActual}")->getFont()->setUnderline(true);
                $hoja->getStyle("B{$filaActual}")->getFont()->getColor()->setRGB(self::COLOR_ENLACE);
            } else {
                $hoja->setCellValue("B{$filaActual}", '—');
            }

            $hoja->setCellValue("C{$filaActual}", $dv->nombre_visitante ?? 'N/A');
            $hoja->setCellValue("D{$filaActual}", $etiquetaTipo($dv->tipo_visitante));
            $hoja->setCellValue("E{$filaActual}", $dv->edificio);
            $hoja->setCellValue("F{$filaActual}", $dv->medico ?? '');
            $hoja->setCellValue("G{$filaActual}", $dv->detalle ?? 'N/A');
            $hoja->setCellValue("H{$filaActual}", $dv->piso ?? '—');
            $hoja->setCellValueExplicit("I{$filaActual}", $dv->fecha_entrada ? date('d/m/Y H:i', strtotime($dv->fecha_entrada)) : 'N/A', DataType::TYPE_STRING);
            $salida = $dv->fecha_salida
                ? date('d/m/Y H:i', strtotime($dv->fecha_salida))
                : ($dv->estado === 'activa' ? 'En curso' : 'N/A');
            $hoja->setCellValueExplicit("J{$filaActual}", $salida, DataType::TYPE_STRING);
            $hoja->setCellValue("K{$filaActual}", $dv->estado);

            $filaActual++;
        }

        return $spreadsheet;
    }

    private static function urlFoto(?string $ruta): ?string
    {
        $baseUrl = rtrim((string) config('app.mobile_uploads_url'), '/');

        if (empty($ruta) || str_starts_with($ruta, 'blob:') || $baseUrl === '') {
            return null;
        }

        return $baseUrl.'/'.ltrim($ruta, '/');
    }
}
