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
        Schema::create('ventas', function (Blueprint $table) {
            $table->id('codigo_venta');
            $table->string('numero_venta', 50)->unique();
            $table->foreignId('cliente_id')->constrained('users')->onDelete('cascade'); // Cliente (user con role CLIENTE)
            $table->foreignId('vendedor_id')->constrained('users')->onDelete('cascade'); // Vendedor que realizó la venta
            $table->timestamp('fecha_venta');
            $table->enum('tipo_pago', ['contado', 'credito']);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('descuento', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->enum('estado', ['pendiente', 'completada', 'cancelada'])->default('completada');
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index('fecha_venta');
            $table->index('cliente_id');
            $table->index('vendedor_id');
            $table->index('tipo_pago');
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
