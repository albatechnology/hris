<?php

namespace App\Models;

use App\Traits\Models\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReprimandRecord extends Model
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
        'reprimand_id',
        'date'
    ];

    public function reprimand(): BelongsTo
    {
        return $this->belongsTo(Reprimand::class);
    }
}
