<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBranch extends BaseModel
{
    public $incrementing = false;

    protected $fillable = ['user_id', 'branch_id'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
