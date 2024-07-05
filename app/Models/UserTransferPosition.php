<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTransferPosition extends BaseModel
{
    public $timestamps = false;
    protected $fillable = ['user_transfer_id', 'department_id', 'position_id'];

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
