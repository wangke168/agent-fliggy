<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductInventory extends Model
{
    use HasFactory;

    protected $table = 'product_inventories';

    protected $fillable = [
        'inventory_date',
        'stock',
        'product_roomtype_id',
    ];

    protected $casts = [
        'inventory_date' => 'date',
    ];

    /**
     * Get the product roomtype that this inventory belongs to.
     */
    public function productRoomtype(): BelongsTo
    {
        return $this->belongsTo(ProductRoomtype::class, 'product_roomtype_id');
    }
}
