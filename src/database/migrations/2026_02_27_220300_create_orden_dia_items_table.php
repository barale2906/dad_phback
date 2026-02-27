<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orden_dia_items', function (Blueprint $table): void {
            $table->id()->comment('Identificador unico del punto del orden del dia.');
            $table->foreignId('reunion_id')
                ->constrained('reuniones')
                ->cascadeOnDelete()
                ->comment('Reunion a la que pertenece el punto del orden del dia.');
            $table->string('titulo', 255)->comment('Titulo del punto del orden del dia.');
            $table->text('descripcion')->nullable()->comment('Descripcion ampliada del punto del orden del dia.');
            $table->unsignedInteger('orden')->comment('Posicion del punto dentro del orden del dia.');
            $table->boolean('ejecutado')->default(false)->comment('Indica si el punto ya fue ejecutado durante la reunion.');
            $table->timestamps();

            $table->unique(['reunion_id', 'orden'], 'uq_orden_dia_reunion_orden');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_dia_items');
    }
};
