<?php

namespace App\Models;

use App\Enums\FieldType;
use App\Traits\Models\CompanyTenanted;
use App\Traits\Models\CustomSoftDeletes;
use Illuminate\Database\Eloquent\Model;

class CustomField extends Model
{
    use CustomSoftDeletes, CompanyTenanted;

    protected $fillable = [
        'company_id',
        'key',
        'type',
        'options',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'key' => 'string',
        'type' => FieldType::class,
        'options' => 'array',
    ];
}
