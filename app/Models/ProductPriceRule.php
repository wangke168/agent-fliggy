<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPriceRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'rule_type',
        'rule_value',
        'price_adjustment',
        'priority',
        'roomtype_id',
        'hotel_id',
        'product_id',
        'tourist_id',
    ];

    protected $casts = [
        'price_adjustment' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(ProductRoomtype::class, 'roomtype_id');
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(ProductHotel::class, 'hotel_id');
    }

    public function tourist(): BelongsTo
    {
        return $this->belongsTo(Tourist::class);
    }
}
