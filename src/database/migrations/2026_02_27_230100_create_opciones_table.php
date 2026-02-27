<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opciones', function (Blueprint $table): void {
            $table->id()->comment('Identificador unico de la opcion de votacion.');
            $table->foreignId('pregunta_id')
                ->constrained('preguntas')
                ->cascadeOnDelete()
                ->comment('Pregunta a la que pertenece la opcion.');
            $table->string('texto', 255)->comment('Texto visible de la opcion de votacion.');
            $table->unsignedInteger('orden')->default(1)->comment('Posicion de la opcion dentro de la pregunta.');
            $table->softDeletes()->comment('Fecha de borrado logico de la opcion.');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opciones');
    }
};
