<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoFacilTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'venta_id',
        'pago_id',
        'cuota_id',
        'pagofacil_transaction_id',
        'company_transaction_id',
        'payment_method_id',
        'payment_method_name',
        'client_name',
        'document_id',
        'phone_number',
        'email',
        'amount',
        'currency',
        'qr_base64',
        'checkout_url',
        'deep_link',
        'qr_content_url',
        'universal_url',
        'expiration_date',
        'status',
        'payment_date',
        'payment_time',
        'order_detail',
        'callback_url',
        'error_message',
        'response_data',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'order_detail' => 'array',
        'response_data' => 'array',
        'expiration_date' => 'datetime',
        'payment_date' => 'datetime',
        'payment_time' => 'datetime',
    ];

    // Relaciones
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id', 'codigo_venta');
    }

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class, 'pago_id', 'codigo_pago');
    }

    public function cuota(): BelongsTo
    {
        return $this->belongsTo(Cuota::class, 'cuota_id', 'codigo_cuota');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    // Helpers
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'payment_date' => now(),
        ]);
    }

    public function markAsExpired(): void
    {
        $this->update(['status' => 'expired']);
    }

    public function markAsFailed(string $errorMessage = null): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }
}
