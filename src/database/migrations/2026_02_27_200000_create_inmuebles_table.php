<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inmuebles', function (Blueprint $table): void {
            $table->id()->comment('Identificador unico del inmueble.');
            $table->string('nomenclatura', 50)->unique()->comment('Codigo unico de identificacion del inmueble en la PH.');
            $table->decimal('coeficiente', 10, 6)->comment('Coeficiente de participacion del inmueble dentro de la PH.');
            $table->string('tipo', 50)->comment('Tipo de inmueble: apartamento, local, casa, parqueadero, puesto, etc.');
            $table->string('propietario_documento', 50)->nullable()->comment('Documento del propietario principal del inmueble.');
            $table->string('propietario_nombre', 255)->nullable()->comment('Nombre del propietario principal del inmueble.');
            $table->string('telefono', 20)->nullable()->comment('Telefono de contacto principal asociado al inmueble.');
            $table->string('email', 255)->nullable()->comment('Correo electronico de contacto principal asociado al inmueble.');
            $table->boolean('activo')->default(true)->comment('Indica si el inmueble esta activo para operaciones en el sistema.');
            $table->softDeletes()->comment('Fecha de borrado logico del inmueble.');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inmuebles');
    }
};
