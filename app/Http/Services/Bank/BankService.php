<?php

namespace App\Http\Services\Bank;

use App\Http\Services\BaseService;
use App\Interfaces\Repositories\Bank\BankRepositoryInterface;
use App\Interfaces\Services\Bank\BankServiceInterface;

class BankService extends BaseService implements BankServiceInterface
{
    public function __construct(protected BankRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}
