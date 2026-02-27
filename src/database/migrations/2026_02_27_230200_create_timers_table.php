<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timers', function (Blueprint $table): void {
            $table->id()->comment('Identificador unico del timer.');
            $table->foreignId('reunion_id')
                ->constrained('reuniones')
                ->cascadeOnDelete()
                ->comment('Reunion a la que pertenece el timer.');
            $table->string('tipo', 30)->comment('Tipo de timer: INTERVENCION o VOTACION.');
            $table->unsignedInteger('duracion_segundos')->comment('Duracion configurada del timer en segundos.');
            $table->timestamp('inicio_at')->nullable()->comment('Fecha y hora real de inicio del timer.');
            $table->timestamp('fin_at')->nullable()->comment('Fecha y hora de finalizacion esperada/real del timer.');
            $table->string('estado', 20)->default('inactivo')->comment('Estado del timer: inactivo, activo, pausado o finalizado.');
            $table->string('interviniente_nombre', 255)->nullable()->comment('Nombre de la persona que usa el tiempo de intervencion.');
            $table->foreignId('interviniente_asistente_id')
                ->nullable()
                ->constrained('asistentes')
                ->nullOnDelete()
                ->comment('Asistente asociado al uso del timer cuando aplica.');
            $table->softDeletes()->comment('Fecha de borrado logico del timer.');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timers');
    }
};
