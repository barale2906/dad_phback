<?php

namespace Tests\Feature;

use App\Models\Asistente;
use App\Models\Inmueble;
use App\Models\Opcion;
use App\Models\Ph;
use App\Models\Pregunta;
use App\Models\Reunion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class VotoApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Pregunta $pregunta;

    private Opcion $opcion;

    private Inmueble $inmueble;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedData();
    }

    private function seedData(): void
    {
        Ph::create([
            'nit' => '900123456',
            'nombre' => 'PH Test',
            'installed_at' => now(),
        ]);

        $this->user = User::factory()->create(['rol' => 'LOGISTICA']);

        $reunion = Reunion::create([
            'tipo' => 'ordinaria',
            'fecha' => now()->toDateString(),
            'hora' => now()->format('H:i:s'),
            'modalidad' => 'presencial',
            'ente' => 'ASAMBLEA',
            'estado' => 'en_curso',
        ]);

        $this->pregunta = Pregunta::create([
            'reunion_id' => $reunion->id,
            'pregunta' => '¿Aprueba?',
            'estado' => 'abierta',
            'orden' => 1,
        ]);

        $this->opcion = Opcion::create([
            'pregunta_id' => $this->pregunta->id,
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

    public function test_post_votos_responde_202_y_encola(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/votos', [
                'pregunta_id' => $this->pregunta->id,
                'opcion_id' => $this->opcion->id,
                'inmueble_id' => $this->inmueble->id,
            ]);

        $response->assertStatus(202)
            ->assertJson([
                'message' => 'Voto recibido y en cola para procesamiento.',
                'status' => 'queued',
            ]);

        // Con QUEUE_CONNECTION=sync, el job se ejecuta inmediatamente
        $this->assertDatabaseCount('votos', 1);
    }

    public function test_post_votos_con_asistente_id(): void
    {
        $asistente = Asistente::create([
            'nombre' => 'Juan Pérez',
            'documento' => '12345678',
            'telefono' => '3001112233',
            'codigo_acceso' => Str::random(10),
            'tipo_asistente' => 'PROPIETARIO',
        ]);
        $asistente->inmuebles()->attach($this->inmueble->id, ['coeficiente' => 5.5]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/votos', [
                'pregunta_id' => $this->pregunta->id,
                'opcion_id' => $this->opcion->id,
                'asistente_id' => $asistente->id,
            ]);

        $response->assertStatus(202);
        $this->assertDatabaseCount('votos', 1);
    }

    public function test_post_votos_sin_inmueble_ni_asistente_falla_422(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/votos', [
                'pregunta_id' => $this->pregunta->id,
                'opcion_id' => $this->opcion->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['inmueble_id']);
    }

    public function test_post_votos_opcion_no_pertenece_a_pregunta_falla_422(): void
    {
        $otraPregunta = Pregunta::create([
            'reunion_id' => $this->pregunta->reunion_id,
            'pregunta' => 'Otra pregunta',
            'estado' => 'inactiva',
            'orden' => 2,
        ]);
        $otraOpcion = Opcion::create([
            'pregunta_id' => $otraPregunta->id,
            'texto' => 'NO',
            'orden' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/votos', [
                'pregunta_id' => $this->pregunta->id,
                'opcion_id' => $otraOpcion->id,
                'inmueble_id' => $this->inmueble->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['opcion_id']);
    }

    public function test_post_votos_sin_autenticacion_falla_401(): void
    {
        $response = $this->postJson('/api/votos', [
            'pregunta_id' => $this->pregunta->id,
            'opcion_id' => $this->opcion->id,
            'inmueble_id' => $this->inmueble->id,
        ]);

        $response->assertStatus(401);
    }

    public function test_get_votos_lista_con_paginacion(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/votos');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }
}
