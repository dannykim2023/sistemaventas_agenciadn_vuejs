<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quotation extends Model
{
    protected $fillable = [
        'number','customer_id','issue_date','valid_until','currency','exchange_rate',
        'status','tax_included','igv_rate','discount_total','subtotal','tax_total','total',
        'notes','terms','sent_at','accepted_at'
    ];

    protected $casts = [
        'issue_date'   => 'date',
        'valid_until'  => 'date',
        'tax_included' => 'bool',
        'sent_at'      => 'datetime',
        'accepted_at'  => 'datetime',
    ];

    public function customer() { return $this->belongsTo(Customer::class); }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class)->orderBy('sort_order');
    }

    /** Recalcula totales desde los ítems */
    public function recalcTotals(): void
    {
        $subtotal = 0; $discountTotal = 0; $taxTotal = 0; $total = 0;

        $this->loadMissing('items');
        foreach ($this->items as $it) {
            $lineBase = $it->quantity * $it->unit_price;
            $discAmt  = $it->discount_pct > 0
                ? round($lineBase * ($it->discount_pct/100), 2)
                : ($it->discount_amount ?? 0);

            $baseAfterDisc = $lineBase - $discAmt;

            if ($this->tax_included) {
                $baseNet  = round($baseAfterDisc / (1 + $it->tax_pct), 2);
                $taxAmt   = round($baseAfterDisc - $baseNet, 2);
                $lineTot  = round($baseAfterDisc, 2);
            } else {
                $taxAmt   = round($baseAfterDisc * $it->tax_pct, 2);
                $lineTot  = round($baseAfterDisc + $taxAmt, 2);
                $baseNet  = $baseAfterDisc;
            }

            $it->discount_amount = $discAmt;
            $it->tax_amount      = $taxAmt;
            $it->line_total      = $lineTot;
            $it->save();

            $subtotal       += $baseNet;
            $discountTotal  += $discAmt;
            $taxTotal       += $taxAmt;
            $total          += $lineTot;
        }

        $this->subtotal       = $subtotal;
        $this->discount_total = $discountTotal;
        $this->tax_total      = $taxTotal;
        $this->total          = $total;
        $this->save();
    }
}
