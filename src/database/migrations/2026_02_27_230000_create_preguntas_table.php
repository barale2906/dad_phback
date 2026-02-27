<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preguntas', function (Blueprint $table): void {
            $table->id()->comment('Identificador unico de la pregunta.');
            $table->foreignId('reunion_id')
                ->constrained('reuniones')
                ->cascadeOnDelete()
                ->comment('Reunion a la que pertenece la pregunta.');
            $table->text('pregunta')->comment('Texto de la pregunta de votacion.');
            $table->string('estado', 20)->default('inactiva')->comment('Estado de la pregunta: inactiva, abierta, cerrada o cancelada.');
            $table->timestamp('apertura_at')->nullable()->comment('Fecha y hora real de apertura de la pregunta.');
            $table->timestamp('cierre_at')->nullable()->comment('Fecha y hora real de cierre de la pregunta.');
            $table->unsignedInteger('orden')->default(1)->comment('Posicion de la pregunta dentro de la reunion.');
            $table->softDeletes()->comment('Fecha de borrado logico de la pregunta.');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preguntas');
    }
};
