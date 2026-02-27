<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistente_inmueble', function (Blueprint $table): void {
            $table->id()->comment('Identificador unico de la relacion asistente-inmueble.');
            $table->foreignId('asistente_id')
                ->constrained('asistentes')
                ->cascadeOnDelete()
                ->comment('Asistente que representa el inmueble.');
            $table->foreignId('inmueble_id')
                ->constrained('inmuebles')
                ->cascadeOnDelete()
                ->comment('Inmueble representado por el asistente.');
            $table->decimal('coeficiente', 10, 6)->comment('Snapshot del coeficiente del inmueble al momento de asociarlo al asistente.');
            $table->string('poder_url')->nullable()->comment('Ruta o URL del documento de poder cuando aplica para apoderados.');
            $table->timestamps();

            $table->unique(['asistente_id', 'inmueble_id'], 'uq_asistente_inmueble');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistente_inmueble');
    }
};
