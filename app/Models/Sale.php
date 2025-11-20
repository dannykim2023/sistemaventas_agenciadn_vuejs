<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'customer_id',
        'quotation_id',
        'series',
        'number',
        'issue_date',
        'due_date',
        'payment_term',
        'currency',
        'subtotal',
        'tax',
        'total',
        'status',
        'notes',
    ];

    // 👇 Para que Inertia/Vue reciban estos campos automáticamente
    protected $appends = ['paid_amount', 'balance'];

    protected $casts = [
        'issue_date' => 'date',
        'due_date'   => 'date',
        'subtotal'   => 'decimal:2',
        'tax'        => 'decimal:2',
        'total'      => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Total pagado
    public function getPaidAmountAttribute(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    // Saldo
    public function getBalanceAttribute(): float
    {
        return (float) ($this->total - $this->paid_amount);
    }

    /**
     * Recalcula el estado de la venta según los pagos
     * Estados sugeridos: issued, partial, paid
     */
    public function refreshPaymentStatus(): void
    {
        $paid  = $this->paid_amount;
        $total = $this->total;

        if ($paid <= 0) {
            $this->status = 'issued';
        } elseif ($paid + 0.01 < $total) {
            $this->status = 'partial';
        } else {
            $this->status = 'paid';
        }

        $this->save();
    }
}
