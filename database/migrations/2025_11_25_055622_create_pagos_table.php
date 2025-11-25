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
        Schema::create('pagos', function (Blueprint $table) {
            $table->id('codigo_pago');
            $table->foreignId('venta_id')->constrained('ventas', 'codigo_venta');
            $table->decimal('monto', 10, 2);
            $table->timestamp('fecha_pago');
            $table->enum('metodo_pago', ['efectivo', 'transferencia', 'tarjeta', 'cheque']);
            $table->string('referencia', 100)->nullable();
            $table->foreignId('usuario_registro_id')->constrained('users'); // Usuario que registró el pago
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index('venta_id');
            $table->index('fecha_pago');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
