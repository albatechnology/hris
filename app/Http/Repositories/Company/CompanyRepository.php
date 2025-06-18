<?php

namespace App\Http\Repositories\Company;

use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\Company\CompanyRepositoryInterface;
use App\Models\Company;

class CompanyRepository extends BaseRepository implements CompanyRepositoryInterface
{
    public function __construct(Company $model)
    {
        parent::__construct($model);
    }
}
