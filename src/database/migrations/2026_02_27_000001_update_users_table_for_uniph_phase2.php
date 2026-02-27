<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('rol', 30)
                ->default('LECTURA')
                ->after('password')
                ->comment('Rol operativo del usuario en UniPH: SUPER_ADMIN, ADMIN_PH, LOGISTICA o LECTURA.');

            $table->string('tipo_usuario', 20)
                ->default('ADMINISTRATIVO')
                ->after('rol')
                ->comment('Clasificacion funcional del usuario: PROPIETARIO, RESIDENTE o ADMINISTRATIVO.');

            $table->string('documento', 50)
                ->nullable()
                ->unique()
                ->after('tipo_usuario')
                ->comment('Numero de documento del usuario, opcional y unico cuando existe.');

            $table->string('telefono', 20)
                ->nullable()
                ->after('documento')
                ->comment('Telefono de contacto del usuario.');

            $table->boolean('activo')
                ->default(true)
                ->after('telefono')
                ->comment('Indica si la cuenta de usuario esta habilitada para autenticarse.');

            $table->softDeletes()->comment('Fecha de borrado logico del usuario para trazabilidad historica.');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropSoftDeletes();
            $table->dropColumn(['activo', 'telefono', 'documento', 'tipo_usuario', 'rol']);
        });
    }
};
