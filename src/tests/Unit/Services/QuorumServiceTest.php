<?php

namespace Tests\Unit\Services;

use App\Models\Inmueble;
use App\Models\Pregunta;
use App\Models\Reunion;
use App\Models\Voto;
use App\Services\QuorumService;
use App\Services\VotoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuorumServiceTest extends TestCase
{
    use RefreshDatabase;

    private QuorumService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QuorumService;
    }

    public function test_calcular_quorum_sin_reunion(): void
    {
        \App\Models\Ph::create([
            'nit' => '900123456',
            'nombre' => 'PH Test',
            'installed_at' => now(),
        ]);

        Inmueble::create([
            'nomenclatura' => 'A101',
            'coeficiente' => 50.0,
            'tipo' => 'apartamento',
            'activo' => true,
        ]);

        Inmueble::create([
            'nomenclatura' => 'A102',
            'coeficiente' => 50.0,
            'tipo' => 'apartamento',
            'activo' => true,
        ]);

        $resultado = $this->service->calcularQuorum(null);

        $this->assertSame(2, $resultado['total_unidades']);
        $this->assertSame(100.0, (float) $resultado['total_coeficiente']);
    }

    public function test_calcular_quorum_con_votos(): void
    {
        \App\Models\Ph::create([
            'nit' => '900123456',
            'nombre' => 'PH Test',
            'installed_at' => now(),
        ]);

        $inm1 = Inmueble::create([
            'nomenclatura' => 'A101',
            'coeficiente' => 60.0,
            'tipo' => 'apartamento',
            'activo' => true,
        ]);

        Inmueble::create([
            'nomenclatura' => 'A102',
            'coeficiente' => 40.0,
            'tipo' => 'apartamento',
            'activo' => true,
        ]);

        $reunion = Reunion::create([
            'tipo' => 'ordinaria',
            'fecha' => now()->toDateString(),
            'hora' => now()->format('H:i:s'),
            'modalidad' => 'presencial',
            'ente' => 'ASAMBLEA',
            'estado' => 'en_curso',
        ]);

        $pregunta = Pregunta::create([
            'reunion_id' => $reunion->id,
            'pregunta' => 'Quorum',
            'estado' => 'abierta',
            'orden' => 0,
        ]);

        $opcion = $pregunta->opciones()->create(['texto' => 'PRESENTE', 'orden' => 1]);

        $votoService = new VotoService;
        $votoService->registrarPorInmueble($pregunta, $opcion, $inm1, null, null);

        $resultado = $this->service->calcularQuorum($reunion);

        $this->assertSame(1, $resultado['unidades_presentes']);
        $this->assertSame(60.0, (float) $resultado['coeficiente_presente']);
        $this->assertSame(50.0, $resultado['porcentaje_unidades']);
        $this->assertSame(60.0, $resultado['porcentaje_coeficiente']);
    }
}
