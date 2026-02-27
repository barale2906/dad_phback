<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('votos', function (Blueprint $table): void {
            $table->id()->comment('Identificador unico del voto registrado.');

            $table->foreignId('pregunta_id')
                ->constrained('preguntas')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('Pregunta de votacion a la que pertenece el voto.');

            $table->foreignId('inmueble_id')
                ->constrained('inmuebles')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('Inmueble que esta representando el voto.');

            $table->foreignId('opcion_id')
                ->constrained('opciones')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('Opcion seleccionada por el inmueble en la pregunta.');

            $table->foreignId('asistente_id')
                ->nullable()
                ->constrained('asistentes')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('Asistente que emite el voto, si aplica, para trazabilidad de quien voto.');

            $table->decimal('coeficiente', 8, 4)
                ->comment('Coeficiente de copropiedad del inmueble al momento de registrar el voto (snapshot).');

            $table->string('telefono', 20)
                ->nullable()
                ->comment('Telefono de contacto usado para registrar el voto, si aplica (por ejemplo desde WhatsApp).');

            $table->timestamp('votado_at')
                ->comment('Fecha y hora exacta en la que se registro el voto.');

            $table->timestamps();

            $table->unique(['pregunta_id', 'inmueble_id'], 'votos_pregunta_inmueble_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votos');
    }
};

