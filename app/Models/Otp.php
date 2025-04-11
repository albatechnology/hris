<?php

namespace App\Models;

use App\Traits\Models\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    use BelongsToUser;

    protected $fillable = [];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $model->expires_at = now()->addHour();
        });
    }

    public function scopeWhereActive(Builder $query)
    {
        $query->where('expires_at', '>=', now());
    }
}
