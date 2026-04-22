<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryMovement extends Model
{
    public const TYPE_IN = 'in';
    public const TYPE_OUT = 'out';

    public const REASON_SALE = 'sale';
    public const REASON_CANCEL_SALE = 'cancel_sale';
    public const REASON_MANUAL_ENTRY = 'manual_entry';
    public const REASON_MANUAL_ADJUSTMENT = 'manual_adjustment';

    protected $fillable = [
        'product_id',
        'user_id',
        'movement_type',
        'reason',
        'quantity',
        'stock_before',
        'stock_after',
        'reference_type',
        'reference_id',
        'occurred_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'stock_before' => 'integer',
            'stock_after' => 'integer',
            'occurred_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
