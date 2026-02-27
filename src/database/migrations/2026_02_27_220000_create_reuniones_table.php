<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reuniones', function (Blueprint $table): void {
            $table->id()->comment('Identificador unico de la reunion.');
            $table->string('tipo', 20)->comment('Tipo de reunion: ordinaria o extraordinaria.');
            $table->date('fecha')->comment('Fecha programada de la reunion.');
            $table->time('hora')->comment('Hora programada de la reunion.');
            $table->string('modalidad', 20)->comment('Modalidad de reunion: presencial, virtual o mixta.');
            $table->string('ente', 30)->default('ASAMBLEA')->comment('Ente convocado: ASAMBLEA, CONSEJO, ADMINISTRADOR o CONTADOR.');
            $table->string('estado', 20)->default('programada')->comment('Estado de la reunion: programada, en_curso, finalizada o cancelada.');
            $table->timestamp('inicio_at')->nullable()->comment('Fecha y hora real de inicio de la reunion.');
            $table->timestamp('cierre_at')->nullable()->comment('Fecha y hora real de cierre de la reunion.');
            $table->softDeletes()->comment('Fecha de borrado logico de la reunion.');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reuniones');
    }
};
