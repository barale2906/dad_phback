<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phs', function (Blueprint $table): void {
            $table->id()->comment('Identificador unico del registro de la Propiedad Horizontal.');
            $table->string('nit')->unique()->comment('NIT unico de la Propiedad Horizontal.');
            $table->string('nombre')->comment('Nombre oficial de la Propiedad Horizontal.');
            $table->string('logo')->nullable()->comment('Ruta o URL del logo institucional.');
            $table->string('email')->nullable()->comment('Correo institucional de contacto de la PH.');
            $table->string('direccion')->nullable()->comment('Direccion fisica principal de la PH.');
            $table->string('telefono', 20)->nullable()->comment('Telefono principal de la PH.');
            $table->string('estado', 20)->default('activo')->comment('Estado operativo de la PH: activo o inactivo.');
            $table->timestamp('installed_at')->nullable()->comment('Fecha/hora en la que el instalador marco el sistema como instalado.');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phs');
    }
};
