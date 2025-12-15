<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRoomtype extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_roomtype';

    protected $fillable = [
        'roomname',
        'roomcode',
        'hotel_id',
        'product_id',
        'tourist_id',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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
