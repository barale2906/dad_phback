<?php

namespace Tests\Unit\Services;

use App\Models\Pregunta;
use App\Models\Reunion;
use App\Services\PreguntaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class PreguntaServiceTest extends TestCase
{
    use RefreshDatabase;

    private PreguntaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PreguntaService;
    }

    public function test_abrir_cambia_estado_a_abierta(): void
    {
        \App\Models\Ph::create([
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

        $pregunta = Pregunta::create([
            'reunion_id' => $reunion->id,
            'pregunta' => '¿Aprueba?',
            'estado' => 'inactiva',
            'orden' => 1,
        ]);

        $this->service->abrir($pregunta);

        $pregunta->refresh();
        $this->assertSame('abierta', $pregunta->estado);
        $this->assertNotNull($pregunta->apertura_at);
    }

    public function test_abrir_pregunta_ya_abierta_lanza_excepcion(): void
    {
        \App\Models\Ph::create([
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

        $pregunta = Pregunta::create([
            'reunion_id' => $reunion->id,
            'pregunta' => '¿Aprueba?',
            'estado' => 'abierta',
            'orden' => 1,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('La pregunta ya esta abierta');

        $this->service->abrir($pregunta);
    }

    public function test_cerrar_cambia_estado_a_cerrada(): void
    {
        \App\Models\Ph::create([
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

        $pregunta = Pregunta::create([
            'reunion_id' => $reunion->id,
            'pregunta' => '¿Aprueba?',
            'estado' => 'abierta',
            'orden' => 1,
        ]);

        $this->service->cerrar($pregunta);

        $pregunta->refresh();
        $this->assertSame('cerrada', $pregunta->estado);
        $this->assertNotNull($pregunta->cierre_at);
    }

    public function test_cerrar_pregunta_no_abierta_lanza_excepcion(): void
    {
        \App\Models\Ph::create([
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

        $pregunta = Pregunta::create([
            'reunion_id' => $reunion->id,
            'pregunta' => '¿Aprueba?',
            'estado' => 'inactiva',
            'orden' => 1,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Solo se puede cerrar una pregunta abierta');

        $this->service->cerrar($pregunta);
    }
}
