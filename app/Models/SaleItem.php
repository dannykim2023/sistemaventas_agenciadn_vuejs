<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id',
        'product_id',
        'description',
        'quantity',
        'unit_price',
        'discount',
        'tax_percent',
        'total',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
