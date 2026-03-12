<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistentes', function (Blueprint $table): void {
            $table->id()->comment('Identificador unico del asistente en esta reunion.');

            $table->foreignId('reunion_id')
                ->constrained('reuniones')
                ->cascadeOnDelete()
                ->comment(
                    'Reunion a la que pertenece este registro de asistencia. '.
                    'Un asistente es efimero: existe solo en el contexto de una reunion especifica.'
                );

            $table->string('telefono', 20)
                ->nullable()
                ->comment(
                    'Numero de telefono del asistente. Se usa para identificarlo cuando '.
                    'se registra por WhatsApp (PRESENTE, SI, NO). Nullable porque puede '.
                    'haberse registrado solo con codigo de barras fisico.'
                );

            $table->unsignedBigInteger('codigo_barras')
                ->nullable()
                ->comment(
                    'Numero del codigo de barras fisico asignado por logistica al momento '.
                    'del registro presencial. Efimero: se asigna en cada reunion desde un '.
                    'paquete de codigos impresos. Nullable porque puede haberse registrado '.
                    'solo por WhatsApp. El mismo codigo puede cubrir multiples inmuebles '.
                    'a traves de la relacion asistente_inmueble.'
                );

            $table->timestamps();

            $table->comment(
                'Registros de presencia por reunion. Cada fila representa a una persona '.
                'que asiste fisicamente o via WhatsApp a una reunion especifica. '.
                'No es un catalogo permanente de personas; los datos se crean el dia '.
                'de cada reunion y se relacionan con los inmuebles que representan.'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistentes');
    }
};
