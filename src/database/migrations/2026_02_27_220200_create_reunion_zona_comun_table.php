<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reunion_zona_comun', function (Blueprint $table): void {
            $table->id()->comment('Identificador unico de la relacion reunion-zona comun.');
            $table->foreignId('reunion_id')
                ->constrained('reuniones')
                ->cascadeOnDelete()
                ->comment('Reunion asociada a la zona comun.');
            $table->foreignId('zona_comun_id')
                ->constrained('zonas_comunes')
                ->cascadeOnDelete()
                ->comment('Zona comun asociada a la reunion.');
            $table->timestamps();

            $table->unique(['reunion_id', 'zona_comun_id'], 'uq_reunion_zona_comun');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reunion_zona_comun');
    }
};
