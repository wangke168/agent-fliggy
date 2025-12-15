<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hotel extends Model
{
    use HasFactory;

    protected $table = 'hotel';

    protected $fillable = [
        'hotel_name',
        'hotel_code',
        'tourist_id',
    ];

    public function tourist(): BelongsTo
    {
        return $this->belongsTo(Tourist::class);
    }

    public function roomTypes(): HasMany
    {
        return $this->hasMany(HotelRoomtype::class, 'hotel_id');
    }
}
