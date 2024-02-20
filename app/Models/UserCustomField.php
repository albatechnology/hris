<?php

namespace App\Models;

use App\Traits\Models\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCustomField extends Model
{
    use BelongsToUser;

    protected $fillable = [
        'custom_field_id',
        'user_id',
        'value',
    ];

    public function customField(): BelongsTo
    {
        return $this->belongsTo(CustomField::class);
    }
}
