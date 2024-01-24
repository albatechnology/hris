<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWhereStatus($query, $value)
    {
        return $query->where('status', $value);
    }

    public function scopeWhereLike($query, $column, $value)
    {
        return $query->where($column, 'LIKE', '%'.$value.'%');
    }

    public function scopeOrWhereLike($query, $column, $value)
    {
        return $query->orWhere($column, 'LIKE', '%'.$value.'%');
    }
}
