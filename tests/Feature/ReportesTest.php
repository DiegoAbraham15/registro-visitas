<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Tests\Concerns\CreaVisitas;
use Tests\TestCase;

class ReportesTest extends TestCase
{
    use CreaVisitas;
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/reportes-graficos')->assertRedirect('/login');
    }

    public function test_user_without_acceso_reportes_is_forbidden(): void
    {
        $user = User::factory()->create(['acceso_reportes' => false]);
        $this->actingAs($user);

        $response = $this->get('/reportes-graficos');

        $response->assertForbidden();
    }

    public function test_user_with_acceso_reportes_can_view_the_report(): void
    {
        $user = User::factory()->create(['acceso_reportes' => true, 'area' => 'hospital']);
        $this->actingAs($user);

        $response = $this->get('/reportes-graficos');

        $response->assertOk();
        $response->assertViewIs('reportes.graficos');
        $response->assertViewHas('periodo', 'mes');
    }

    public function test_non_admin_report_is_scoped_to_their_own_area(): void
    {
        $this->creaVisitaFamiliar(['id_edificio' => 1]);
        $this->creaVisitaTorre(['id_edificio' => 2]);

        $user = User::factory()->create(['acceso_reportes' => true, 'area' => 'hospital']);
        $this->actingAs($user);

        $response = $this->get('/reportes-graficos?periodo=todo');

        $response->assertViewHas('porEdificio', function ($porEdificio) {
            return $porEdificio->pluck('edificio')->all() === ['Hospital'];
        });
    }

    public function test_admin_report_consolidates_every_area(): void
    {
        $this->creaVisitaFamiliar(['id_edificio' => 1]);
        $this->creaVisitaTorre(['id_edificio' => 2]);

        $admin = User::factory()->admin()->create(['acceso_reportes' => true]);
        $this->actingAs($admin);

        $response = $this->get('/reportes-graficos?periodo=todo');

        $response->assertViewHas('areaNombre', 'Todas las áreas');
        $response->assertViewHas('porEdificio', function ($porEdificio) {
            return $porEdificio->count() === 2;
        });
    }

    public function test_periodo_todo_includes_old_visits_while_mes_excludes_them(): void
    {
        $this->creaVisitaFamiliar(['id_edificio' => 1, 'fecha_entrada' => now()]);
        $this->creaVisitaFamiliar(['id_edificio' => 1, 'fecha_entrada' => now()->subMonths(2)]);

        $admin = User::factory()->admin()->create(['acceso_reportes' => true]);
        $this->actingAs($admin);

        $this->get('/reportes-graficos?periodo=mes')
            ->assertViewHas('detalleVisitas', fn ($detalle) => $detalle->count() === 1);

        $this->get('/reportes-graficos?periodo=todo')
            ->assertViewHas('detalleVisitas', fn ($detalle) => $detalle->count() === 2);
    }

    public function test_unknown_periodo_falls_back_to_mes(): void
    {
        $user = User::factory()->create(['acceso_reportes' => true]);
        $this->actingAs($user);

        $response = $this->get('/reportes-graficos?periodo=no-existe');

        $response->assertViewHas('periodo', 'mes');
    }

    public function test_pdf_route_is_forbidden_without_acceso_reportes(): void
    {
        $user = User::factory()->create(['acceso_reportes' => false]);
        $this->actingAs($user);

        $this->get('/reportes-graficos/pdf')->assertForbidden();
    }

    public function test_pdf_route_downloads_a_pdf_file(): void
    {
        $this->creaVisitaFamiliar(['id_edificio' => 1]);

        $user = User::factory()->create(['acceso_reportes' => true, 'area' => 'hospital']);
        $this->actingAs($user);

        $response = $this->get('/reportes-graficos/pdf?periodo=todo');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString(
            'reporte-visitas-todo-',
            $response->headers->get('content-disposition')
        );
    }

    public function test_csv_route_is_forbidden_without_acceso_reportes(): void
    {
        $user = User::factory()->create(['acceso_reportes' => false]);
        $this->actingAs($user);

        $this->get('/reportes-graficos/csv')->assertForbidden();
    }

    public function test_csv_route_downloads_a_csv_file_with_the_detail_rows(): void
    {
        $this->creaVisitaFamiliar(['id_edificio' => 1], ['nombre' => 'Ana Detalle']);

        $user = User::factory()->create(['acceso_reportes' => true, 'area' => 'hospital']);
        $this->actingAs($user);

        $response = $this->get('/reportes-graficos/csv?periodo=todo');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
        $this->assertStringContainsString(
            'reporte-visitas-todo-',
            $response->headers->get('content-disposition')
        );
        $this->assertStringContainsString('Ana Detalle', $response->streamedContent());
    }

    public function test_csv_route_forces_entrada_and_salida_to_text_so_excel_keeps_the_time(): void
    {
        $entrada = Carbon::create(2026, 7, 16, 14, 30);
        $salida = Carbon::create(2026, 7, 16, 16, 45);
        $this->creaVisitaFamiliar([
            'id_edificio' => 1,
            'estado' => 'finalizada',
            'fecha_entrada' => $entrada,
            'fecha_salida' => $salida,
        ]);

        $user = User::factory()->create(['acceso_reportes' => true, 'area' => 'hospital']);
        $this->actingAs($user);

        $contenido = $this->get('/reportes-graficos/csv?periodo=todo')->streamedContent();

        // Prefijo de comilla simple: Excel lo interpreta como "forzar texto" y
        // no reformatea la celda a su formato corto de fecha (que oculta la hora).
        $this->assertStringContainsString("'16/07/2026 14:30", $contenido);
        $this->assertStringContainsString("'16/07/2026 16:45", $contenido);
    }

    public function test_csv_route_neutralizes_leading_formula_characters_in_free_text_fields(): void
    {
        $this->creaVisitaFamiliar(['id_edificio' => 1], ['nombre' => '=HYPERLINK("http://evil.example","click")']);

        $user = User::factory()->create(['acceso_reportes' => true, 'area' => 'hospital']);
        $this->actingAs($user);

        $contenido = $this->get('/reportes-graficos/csv?periodo=todo')->streamedContent();

        // La celda debe quedar como texto inerte (prefijo de comilla simple),
        // nunca como una fórmula que Excel/Sheets pueda evaluar al abrirla.
        $this->assertStringContainsString('\'=HYPERLINK', $contenido);
        $this->assertStringNotContainsString(',=HYPERLINK', $contenido);
    }

    /**
     * Carga el .xlsx devuelto por streamedContent() en un archivo temporal
     * para poder leerlo con PhpSpreadsheet (streamedContent() solo da los
     * bytes crudos; el formato .xlsx es un zip, no se puede inspeccionar
     * como texto).
     */
    private function cargarHojaDesdeRespuesta(string $bytesXlsx): Worksheet
    {
        $rutaTemporal = tempnam(sys_get_temp_dir(), 'test_xlsx_').'.xlsx';
        file_put_contents($rutaTemporal, $bytesXlsx);

        $hoja = IOFactory::load($rutaTemporal)->getActiveSheet();
        unlink($rutaTemporal);

        return $hoja;
    }

    public function test_excel_route_is_forbidden_without_acceso_reportes(): void
    {
        $user = User::factory()->create(['acceso_reportes' => false]);
        $this->actingAs($user);

        $this->get('/reportes-graficos/excel')->assertForbidden();
    }

    public function test_excel_route_downloads_an_xlsx_file(): void
    {
        $this->creaVisitaFamiliar(['id_edificio' => 1], ['nombre' => 'Ana Detalle']);

        $user = User::factory()->create(['acceso_reportes' => true, 'area' => 'hospital']);
        $this->actingAs($user);

        $response = $this->get('/reportes-graficos/excel?periodo=todo');

        $response->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('content-type')
        );
        $this->assertStringContainsString(
            'reporte-visitas-todo-',
            $response->headers->get('content-disposition')
        );

        $hoja = $this->cargarHojaDesdeRespuesta($response->streamedContent());
        $this->assertSame('Ana Detalle', $hoja->getCell('C3')->getValue());
    }

    public function test_excel_route_includes_the_doctor_name_for_torre_visits(): void
    {
        $this->creaVisitaTorre(['id_edificio' => 2], ['nombre' => 'Visitante Torre', 'nombre_medico' => 'DR. JUAN PEREZ']);

        $admin = User::factory()->admin()->create(['acceso_reportes' => true]);
        $this->actingAs($admin);

        $response = $this->get('/reportes-graficos/excel?periodo=todo');

        $hoja = $this->cargarHojaDesdeRespuesta($response->streamedContent());
        $this->assertSame('Médico', $hoja->getCell('F2')->getValue());
        $this->assertSame('DR. JUAN PEREZ', $hoja->getCell('F3')->getValue());
    }

    public function test_excel_route_leaves_medico_blank_for_hospital_visits(): void
    {
        $this->creaVisitaFamiliar(['id_edificio' => 1], ['nombre' => 'Ana Detalle']);

        $user = User::factory()->create(['acceso_reportes' => true, 'area' => 'hospital']);
        $this->actingAs($user);

        $response = $this->get('/reportes-graficos/excel?periodo=todo');

        $hoja = $this->cargarHojaDesdeRespuesta($response->streamedContent());
        $this->assertSame('', (string) $hoja->getCell('F3')->getValue());
    }

    public function test_excel_route_links_to_the_visitors_photo_instead_of_embedding_it(): void
    {
        // Se enlaza en vez de embeberse: descargar cada foto por HTTP al generar
        // el archivo dejaba la petición colgada cuando el servidor de fotos de la
        // app móvil está lento o inalcanzable — un enlace es instantáneo.
        config(['app.mobile_uploads_url' => 'http://fake-mobile-app.test']);

        $this->creaVisitaFamiliar(['id_edificio' => 1], ['nombre' => 'Ana Detalle', 'foto_persona' => '/uploads/ana.png']);

        $user = User::factory()->create(['acceso_reportes' => true, 'area' => 'hospital']);
        $this->actingAs($user);

        $response = $this->get('/reportes-graficos/excel?periodo=todo');

        $response->assertOk();
        $hoja = $this->cargarHojaDesdeRespuesta($response->streamedContent());
        $this->assertSame('Ver foto', $hoja->getCell('B3')->getValue());
        $this->assertSame('http://fake-mobile-app.test/uploads/ana.png', $hoja->getCell('B3')->getHyperlink()->getUrl());
        $this->assertCount(0, $hoja->getDrawingCollection());
    }

    public function test_excel_route_shows_a_dash_when_there_is_no_photo(): void
    {
        $this->creaVisitaFamiliar(['id_edificio' => 1], ['nombre' => 'Ana Detalle', 'foto_persona' => null]);

        $user = User::factory()->create(['acceso_reportes' => true, 'area' => 'hospital']);
        $this->actingAs($user);

        $response = $this->get('/reportes-graficos/excel?periodo=todo');

        $hoja = $this->cargarHojaDesdeRespuesta($response->streamedContent());
        $this->assertSame('—', $hoja->getCell('B3')->getValue());
        $this->assertSame('', $hoja->getCell('B3')->getHyperlink()->getUrl());
    }
}
