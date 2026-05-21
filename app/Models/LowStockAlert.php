<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LowStockAlert extends Model
{
    protected $fillable = [
        'product_id',
        'stock',
        'min_stock',
        'is_active',
        'last_detected_at',
        'last_notified_at',
        'resolved_at',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
