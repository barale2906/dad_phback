<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('convocatorias', function (Blueprint $table): void {
            $table->id()->comment('Identificador unico de la convocatoria.');
            $table->foreignId('reunion_id')
                ->unique()
                ->constrained('reuniones')
                ->cascadeOnDelete()
                ->comment('Reunion asociada a la convocatoria (una convocatoria por reunion).');
            $table->date('fecha_convocatoria')->comment('Fecha en que se realiza la convocatoria.');
            $table->string('medio', 20)->comment('Medio de convocatoria: email, fisico, whatsapp o mixto.');
            $table->longText('contenido')->comment('Contenido formal de la convocatoria.');
            $table->longText('orden_dia_snapshot')->nullable()->comment('Snapshot del orden del dia incluido en la convocatoria.');
            $table->date('fecha_limite_legal')->nullable()->comment('Fecha limite legal calculada para cumplimiento normativo.');
            $table->string('estado', 20)->default('borrador')->comment('Estado de la convocatoria: borrador, enviada, publicada o cerrada.');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('convocatorias');
    }
};
