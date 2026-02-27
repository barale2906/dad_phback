<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistentes', function (Blueprint $table): void {
            $table->id()->comment('Identificador unico del asistente.');
            $table->foreignId('usuario_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Usuario del sistema asociado al asistente, cuando exista.');
            $table->string('nombre')->comment('Nombre completo del asistente.');
            $table->string('documento', 50)->nullable()->comment('Documento de identificacion del asistente.');
            $table->string('telefono', 20)->nullable()->comment('Telefono de contacto del asistente.');
            $table->string('codigo_acceso', 50)->unique()->comment('Codigo de acceso unico para ingreso o validacion del asistente.');
            $table->unsignedBigInteger('barcode_numero')->nullable()->comment('Numero de codigo de barras asignado al asistente.');
            $table->string('tipo_asistente', 20)->default('INVITADO')->comment('Tipo del asistente: PROPIETARIO, RESIDENTE, APODERADO o INVITADO.');
            $table->softDeletes()->comment('Fecha de borrado logico del asistente.');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistentes');
    }
};
