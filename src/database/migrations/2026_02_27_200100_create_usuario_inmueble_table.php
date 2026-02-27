<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuario_inmueble', function (Blueprint $table): void {
            $table->id()->comment('Identificador unico de la relacion usuario-inmueble.');
            $table->foreignId('usuario_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Usuario relacionado con el inmueble.');
            $table->foreignId('inmueble_id')
                ->constrained('inmuebles')
                ->cascadeOnDelete()
                ->comment('Inmueble relacionado con el usuario.');
            $table->string('relacion', 20)->comment('Tipo de relacion: PROPIETARIO, RESIDENTE, ARRENDATARIO o APODERADO.');
            $table->boolean('es_principal')->default(false)->comment('Indica si esta relacion es la principal para el usuario respecto al inmueble.');
            $table->date('fecha_inicio')->nullable()->comment('Fecha de inicio de la relacion entre usuario e inmueble.');
            $table->date('fecha_fin')->nullable()->comment('Fecha de fin de la relacion entre usuario e inmueble.');
            $table->timestamps();

            $table->unique(['usuario_id', 'inmueble_id', 'relacion'], 'uq_usuario_inmueble_relacion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario_inmueble');
    }
};
