<?php

namespace App\Http\Repositories\Reimbursement;

use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\Reimbursement\ReimbursementRepositoryInterface;
use App\Models\Reimbursement;

class ReimbursementRepository extends BaseRepository implements ReimbursementRepositoryInterface
{
    public function __construct(Reimbursement $model)
    {
        parent::__construct($model);
    }
}
