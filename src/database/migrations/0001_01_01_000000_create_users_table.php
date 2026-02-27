<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id()->comment('Identificador unico del usuario.');
            $table->string('name')->comment('Nombre completo del usuario.');
            $table->string('email')->unique()->comment('Correo electronico unico del usuario.');
            $table->timestamp('email_verified_at')->nullable()->comment('Fecha de verificacion del correo electronico.');
            $table->string('password')->comment('Contrasena hasheada del usuario.');
            $table->rememberToken()->comment('Token de sesion persistente para recordarme.');
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary()->comment('Correo del usuario que solicita recuperar su contrasena.');
            $table->string('token')->comment('Token de restablecimiento de contrasena.');
            $table->timestamp('created_at')->nullable()->comment('Fecha de creacion del token de restablecimiento.');
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary()->comment('Identificador unico de la sesion.');
            $table->foreignId('user_id')->nullable()->index()->comment('Usuario asociado a la sesion, cuando existe autenticacion.');
            $table->string('ip_address', 45)->nullable()->comment('Direccion IP de origen de la sesion.');
            $table->text('user_agent')->nullable()->comment('Agente de usuario del navegador o cliente.');
            $table->longText('payload')->comment('Datos serializados de la sesion.');
            $table->integer('last_activity')->index()->comment('Marca de tiempo de la ultima actividad de la sesion.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
