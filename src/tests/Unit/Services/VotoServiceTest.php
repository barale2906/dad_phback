<?php

namespace Tests\Unit\Services;

use App\Models\Asistente;
use App\Models\Inmueble;
use App\Models\Opcion;
use App\Models\Pregunta;
use App\Models\Reunion;
use App\Models\Voto;
use App\Services\VotoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class VotoServiceTest extends TestCase
{
    use RefreshDatabase;

    private VotoService $service;

    private Pregunta $preguntaAbierta;

    private Opcion $opcion;

    private Inmueble $inmueble;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new VotoService;
        $this->seedData();
    }

    private function seedData(): void
    {
        $ph = \App\Models\Ph::create([
            'nit' => '900123456',
            'nombre' => 'PH Test',
            'installed_at' => now(),
        ]);

        $reunion = Reunion::create([
            'tipo' => 'ordinaria',
            'fecha' => now()->toDateString(),
            'hora' => now()->format('H:i:s'),
            'modalidad' => 'presencial',
            'ente' => 'ASAMBLEA',
            'estado' => 'en_curso',
        ]);

        $this->preguntaAbierta = Pregunta::create([
            'reunion_id' => $reunion->id,
            'pregunta' => '¿Aprueba el reglamento?',
            'estado' => 'abierta',
            'orden' => 1,
        ]);

        $this->opcion = Opcion::create([
            'pregunta_id' => $this->preguntaAbierta->id,
            'texto' => 'SÍ',
            'orden' => 1,
        ]);

        $this->inmueble = Inmueble::create([
            'nomenclatura' => 'A101',
            'coeficiente' => 5.5,
            'tipo' => 'apartamento',
            'activo' => true,
        ]);
    }

    public function test_registrar_por_inmueble_crea_voto(): void
    {
        $voto = $this->service->registrarPorInmueble(
            $this->preguntaAbierta,
            $this->opcion,
            $this->inmueble,
            null,
            null
        );

        $this->assertInstanceOf(Voto::class, $voto);
        $this->assertDatabaseCount('votos', 1);
        $this->assertDatabaseHas('votos', [
            'pregunta_id' => $this->preguntaAbierta->id,
            'inmueble_id' => $this->inmueble->id,
            'opcion_id' => $this->opcion->id,
        ]);
    }

    public function test_registrar_duplicado_retorna_null(): void
    {
        $this->service->registrarPorInmueble(
            $this->preguntaAbierta,
            $this->opcion,
            $this->inmueble,
            null,
            null
        );

        $resultado = $this->service->registrarPorInmueble(
            $this->preguntaAbierta,
            $this->opcion,
            $this->inmueble,
            null,
            null
        );

        $this->assertNull($resultado);
        $this->assertDatabaseCount('votos', 1);
    }

    public function test_registrar_pregunta_cerrada_lanza_excepcion(): void
    {
        $this->preguntaAbierta->update(['estado' => 'cerrada']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Solo se pueden registrar votos para preguntas abiertas');

        $this->service->registrarPorInmueble(
            $this->preguntaAbierta,
            $this->opcion,
            $this->inmueble,
            null,
            null
        );
    }

    public function test_registrar_inmueble_inactivo_lanza_excepcion(): void
    {
        $this->inmueble->update(['activo' => false]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No se pueden registrar votos para inmuebles inactivos');

        $this->service->registrarPorInmueble(
            $this->preguntaAbierta,
            $this->opcion,
            $this->inmueble,
            null,
            null
        );
    }

    public function test_registrar_por_asistente_crea_votos_para_cada_inmueble(): void
    {
        $inmueble2 = Inmueble::create([
            'nomenclatura' => 'A102',
            'coeficiente' => 4.0,
            'tipo' => 'apartamento',
            'activo' => true,
        ]);

        $asistente = Asistente::create([
            'nombre' => 'Juan Pérez',
            'documento' => '12345678',
            'telefono' => '3001112233',
            'codigo_acceso' => Str::random(10),
            'tipo_asistente' => 'PROPIETARIO',
        ]);

        $asistente->inmuebles()->attach($this->inmueble->id, ['coeficiente' => 5.5]);
        $asistente->inmuebles()->attach($inmueble2->id, ['coeficiente' => 4.0]);

        $this->service->registrarPorAsistente($this->preguntaAbierta, $this->opcion, $asistente, null);

        $this->assertDatabaseCount('votos', 2);
    }
}
