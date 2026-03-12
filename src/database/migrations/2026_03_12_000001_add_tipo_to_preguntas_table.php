<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('preguntas', function (Blueprint $table): void {
            $table->string('tipo', 20)
                ->default('VOTACION')
                ->after('pregunta')
                ->comment(
                    'Tipo de pregunta: VOTACION (pregunta real de votacion) o '.
                    'QUORUM_CHECK (verificacion de presencia). '.
                    'Solo las VOTACION bloquean la edicion del barcode_numero de asistentes.'
                );
        });
    }

    public function down(): void
    {
        Schema::table('preguntas', function (Blueprint $table): void {
            $table->dropColumn('tipo');
        });
    }
};
