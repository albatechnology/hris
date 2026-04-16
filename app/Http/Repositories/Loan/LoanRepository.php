<?php

namespace App\Http\Repositories\Loan;

use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\Loan\LoanRepositoryInterface;
use App\Models\Loan;

class LoanRepository extends BaseRepository implements LoanRepositoryInterface
{
    public function __construct(Loan $model)
    {
        parent::__construct($model);
    }
}
