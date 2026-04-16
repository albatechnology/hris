<?php

namespace App\Services\CustomField;

use App\Http\Services\BaseService;
use App\Interfaces\Repositories\CustomField\CustomFieldRepositoryInterface;
use App\Interfaces\Services\CustomField\CustomFieldServiceInterface;

class CustomFieldService extends BaseService implements CustomFieldServiceInterface
{
    public function __construct(protected CustomFieldRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}