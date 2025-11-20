<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationItem extends Model
{
    protected $fillable = [
        'quotation_id','product_id','description','quantity','unit',
        'unit_price','discount_pct','discount_amount','tax_pct','tax_amount','line_total','sort_order'
    ];

    public function quotation() { return $this->belongsTo(Quotation::class); }
    public function product()   { return $this->belongsTo(Product::class); }
}
