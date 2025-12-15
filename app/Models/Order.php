<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'order_time',
        'arrive_date',
        'contact_name',
        'contact_tel',
        'guest_name',
        'guest_tel',
        'order_source',
        'order_source_id',
        'order_status',
        'order_amount',
        'roomtype_id',
        'hotel_id',
        'product_id',
        'tourist_id',
    ];

    protected $casts = [
        'order_time' => 'datetime',
        'arrive_date' => 'date',
        'order_amount' => 'decimal:2',
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
