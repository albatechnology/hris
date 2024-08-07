<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTransferBranch extends BaseModel
{
    public $timestamps = false;
    protected $fillable = ['user_transfer_id', 'branch_id'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
