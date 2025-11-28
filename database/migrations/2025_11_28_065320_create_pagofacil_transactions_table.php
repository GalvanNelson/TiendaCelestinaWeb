<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagofacil_transactions', function (Blueprint $table) {
            $table->id();

            // Relación con tu sistema
            $table->foreignId('venta_id')->nullable()->constrained('ventas', 'codigo_venta')->onDelete('cascade');
            $table->foreignId('pago_id')->nullable()->constrained('pagos', 'codigo_pago')->onDelete('set null');
            $table->foreignId('cuota_id')->nullable()->constrained('cuotas', 'codigo_cuota')->onDelete('set null');

            // Datos de PagoFácil
            $table->string('pagofacil_transaction_id')->unique()->nullable(); // ID de PagoFácil
            $table->string('company_transaction_id')->unique(); // Tu ID interno (numero_venta + timestamp)
            $table->integer('payment_method_id')->nullable();
            $table->string('payment_method_name')->nullable();

            // Datos del cliente
            $table->string('client_name');
            $table->string('document_id');
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();

            // Montos
            $table->decimal('amount', 10, 2);
            $table->integer('currency')->default(2); // 2 = BOB

            // QR Data
            $table->text('qr_base64')->nullable();
            $table->text('checkout_url')->nullable();
            $table->text('deep_link')->nullable();
            $table->text('qr_content_url')->nullable();
            $table->text('universal_url')->nullable();
            $table->timestamp('expiration_date')->nullable();

            // Estado de la transacción
            $table->enum('status', [
                'pending',      // Generado, esperando pago
                'completed',    // Pagado exitosamente
                'expired',      // QR expirado
                'failed',       // Fallo en el pago
                'cancelled'     // Cancelado manualmente
            ])->default('pending');

            $table->timestamp('payment_date')->nullable();
            $table->timestamp('payment_time')->nullable();

            // Detalles adicionales
            $table->json('order_detail')->nullable();
            $table->text('callback_url')->nullable();
            $table->text('error_message')->nullable();

            // Metadata
            $table->json('response_data')->nullable(); // Respuesta completa de PagoFácil
            $table->timestamps();

            // Índices
            $table->index('venta_id');
            $table->index('status');
            $table->index('payment_date');
            $table->index(['company_transaction_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagofacil_transactions');
    }
};
