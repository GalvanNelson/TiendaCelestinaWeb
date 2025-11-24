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
        Schema::create('entrada_stocks', function (Blueprint $table) {
            $table->integer('codigo_entrada')->primary()->autoIncrement();
            $table->integer('codigo_producto');
            $table->foreign('codigo_producto')
                  ->references('codigo_producto')
                  ->on('productos')
                  ->cascadeOnDelete();

            $table->integer('cantidad');
            $table->string('motivo', 150)->nullable();
            $table->string('usuario', 100)->nullable();
            $table->timestamp('fecha')->useCurrent();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entrada_stocks');
    }
};
