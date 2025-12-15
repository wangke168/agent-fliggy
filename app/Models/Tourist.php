<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tourist extends Model
{
    use HasFactory;

    protected $table = 'tourist';

    protected $fillable = ['name', 'system_integrator_id'];

    public function systemIntegrator(): BelongsTo
    {
        return $this->belongsTo(SystemIntegrator::class);
    }
}
