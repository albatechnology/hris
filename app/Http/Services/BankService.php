<?php

namespace App\Http\Services;

use App\Interfaces\Repositories\BankRepositoryInterface;
use App\Interfaces\Services\BankServiceInterface;

class BankService extends BaseService implements BankServiceInterface
{
    public function __construct(protected BankRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}
