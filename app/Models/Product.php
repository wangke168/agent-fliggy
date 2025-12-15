<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    // No longer need to specify table name, as 'products' is the default for the 'Product' model.

    protected $fillable = [
        'productname',
        'startselldate',
        'endselldate',
        'startverifdate',
        'endverifdate',
        'usedays',
        'base_price',
        'product_type',
        'tourist_id',
        'ota_id',
        'online',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'startselldate' => 'date',
        'endselldate' => 'date',
        'startverifdate' => 'date',
        'endverifdate' => 'date',
        'online' => 'boolean',
    ];

    public function tourist(): BelongsTo
    {
        return $this->belongsTo(Tourist::class);
    }

    public function ota(): BelongsTo
    {
        return $this->belongsTo(Ota::class, 'ota_id');
    }

    public function hotels(): HasMany
    {
        return $this->hasMany(ProductHotel::class);
    }

    public function roomTypes(): HasMany
    {
        return $this->hasMany(ProductRoomtype::class);
    }

    public function priceRules(): HasMany
    {
        return $this->hasMany(ProductPriceRule::class);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(ProductInventory::class);
    }
}
