<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotelRoomtype extends Model
{
    use HasFactory;

    protected $table = 'hotel_roomtype';

    protected $fillable = [
        'roomtype',
        'hotel_id',
        'tourist_id',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }

    public function tourist(): BelongsTo
    {
        return $this->belongsTo(Tourist::class);
    }
}
