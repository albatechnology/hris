<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientLocation extends BaseModel
{
    use SoftDeletes;

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
