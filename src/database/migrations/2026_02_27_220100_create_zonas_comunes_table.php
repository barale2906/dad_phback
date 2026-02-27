<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zonas_comunes', function (Blueprint $table): void {
            $table->id()->comment('Identificador unico de la zona comun.');
            $table->string('nombre', 120)->comment('Nombre de la zona comun.');
            $table->text('descripcion')->nullable()->comment('Descripcion detallada de la zona comun.');
            $table->unsignedInteger('capacidad')->nullable()->comment('Capacidad maxima de personas para la zona comun.');
            $table->string('tipo', 50)->comment('Tipo de zona comun: salon, piscina, gimnasio, etc.');
            $table->boolean('activo')->default(true)->comment('Indica si la zona comun esta activa para uso operativo.');
            $table->softDeletes()->comment('Fecha de borrado logico de la zona comun.');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zonas_comunes');
    }
};
