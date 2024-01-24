<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Department extends BaseModel
{
    protected $fillable = [
        'division_id',
        'name',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
