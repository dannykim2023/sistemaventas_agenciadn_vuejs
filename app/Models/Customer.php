<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'type','document_type','document_number','name','trade_name',
        'email','phone','address','district','province','department',
        'notes','is_active','created_by'
    ];

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }
}
