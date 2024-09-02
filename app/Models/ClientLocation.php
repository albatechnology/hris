<?php

namespace App\Models;

use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientLocation extends BaseModel
{
    use CustomSoftDeletes;

    protected $fillable = [
        'client_id',
        'name',
        'lat',
        'lng',
        'address',
        'description',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
