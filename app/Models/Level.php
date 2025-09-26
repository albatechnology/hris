<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Level extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['company_id','name'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
