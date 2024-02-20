<?php

namespace App\Models;

use App\Enums\FieldType;
use App\Traits\Models\CompanyTenanted;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CustomField extends Model
{
    use CompanyTenanted;

    protected $fillable = [
        'company_id',
        'key',
        'type',
        'values',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'key' => 'string',
        'type' => FieldType::class,
        'values' => 'array',
    ];

    public function customFields(): BelongsToMany
    {
        return $this->belongsToMany(CustomFields::class, 'user_custom_fields', 'custom_field_id', 'user_id');
    }
}
