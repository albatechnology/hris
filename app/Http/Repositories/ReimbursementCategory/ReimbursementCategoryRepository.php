<?php

namespace App\Http\Repositories\ReimbursementCategory;

use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\ReimbursementCategory\ReimbursementCategoryRepositoryInterface;
use App\Models\ReimbursementCategory;

class ReimbursementCategoryRepository extends BaseRepository implements ReimbursementCategoryRepositoryInterface
{
    public function __construct(ReimbursementCategory $model)
    {
        parent::__construct($model);
    }
}
