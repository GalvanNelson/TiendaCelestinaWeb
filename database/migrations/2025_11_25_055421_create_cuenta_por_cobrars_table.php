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
        Schema::create('cuenta_por_cobrars', function (Blueprint $table) {
            $table->id('codigo_cuenta');
            $table->unsignedBigInteger('venta_id');
            $table->decimal('monto_total', 10, 2);
            $table->decimal('monto_pagado', 10, 2)->default(0);
            $table->decimal('saldo_pendiente', 10, 2);
            $table->date('fecha_vencimiento')->nullable();
            $table->enum('estado', ['pendiente', 'parcial', 'pagado', 'vencido'])->default('pendiente');
            $table->text('notas')->nullable();
            $table->timestamps();

            // Relación con ventas
            $table->foreign('venta_id')
                ->references('codigo_venta')
                ->on('ventas')
                ->onDelete('cascade');

            $table->index('venta_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuenta_por_cobrars');
    }
};
