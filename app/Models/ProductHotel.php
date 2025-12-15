<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductHotel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_hotel';

    protected $fillable = [
        'hotelname',
        'hotelcode',
        'product_id',
        'tourist_id',
        'online',
    ];

    protected $casts = [
        'online' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function tourist(): BelongsTo
    {
        return $this->belongsTo(Tourist::class);
    }

    public function roomtypes(): HasMany
    {
        return $this->hasMany(ProductRoomtype::class, 'hotel_id');
    }
}
