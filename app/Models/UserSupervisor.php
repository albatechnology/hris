<?php

namespace App\Models;

use App\Traits\Models\BelongsToUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSupervisor extends BaseModel
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
        'supervisor_id',
        'order',
        'is_additional_supervisor',
    ];

    protected $casts = [
        'is_additional_supervisor' => 'boolean',
    ];

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }
}
