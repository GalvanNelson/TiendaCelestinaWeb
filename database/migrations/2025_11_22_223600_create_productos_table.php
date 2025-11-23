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
        Schema::create('productos', function (Blueprint $table) {
            $table->integer('codigo_producto')->primary()->autoIncrement();
            $table->string('nombre');
            $table->string('imagen')->nullable();
            $table->decimal('precio_unitario', 10, 2);
            $table->integer('stock')->default(0);
            $table->timestamps();

             // FK hacia categorias.codigo_categoria
            $table->integer('categoria_codigo')->nullable();
            $table->foreign('categoria_codigo')
                ->references('codigo_categoria')
                ->on('categorias')
                ->nullOnDelete();

            // FK hacia unidad_medidas.codigo_unidad
            $table->integer('unidad_codigo')->nullable();
            $table->foreign('unidad_codigo')
                ->references('codigo_medida')
                ->on('unidad_medidas')
                ->nullOnDelete();
                });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
