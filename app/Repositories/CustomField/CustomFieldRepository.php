<?php

namespace App\Repositories\CustomField;

use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\CustomField\CustomFieldRepositoryInterface;
use App\Models\CustomField;

class CustomFieldRepository extends BaseRepository implements CustomFieldRepositoryInterface
{
    public function __construct(CustomField $model)
    {
        parent::__construct($model);
    }
}