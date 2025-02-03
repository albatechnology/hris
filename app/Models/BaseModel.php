<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function scopeActive(Builder $query)
    {
        $query->where('is_active', true);
    }

    public function scopeWhereStatus(Builder $query, $value)
    {
        $query->where('status', $value);
    }

    public function scopeWhereLike(Builder $query, $column, $value)
    {
        $query->where($column, 'LIKE', '%' . $value . '%');
    }

    public function scopeOrWhereLike(Builder $query, $column, $value)
    {
        $query->orWhere($column, 'LIKE', '%' . $value . '%');
    }

    public function scopeCreatedAtStart(Builder $query, $date)
    {
        $query->whereDate('created_at', '>=', date('Y-m-d', strtotime($date)));
    }

    public function scopeCreatedAtEnd(Builder $query, $date)
    {
        $query->whereDate('created_at', '<=', date('Y-m-d', strtotime($date)));
    }
}
