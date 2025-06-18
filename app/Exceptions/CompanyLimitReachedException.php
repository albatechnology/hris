<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class CompanyLimitReachedException extends HttpException
{
    public function __construct(string $message = "You have reached the maximum number of companies")
    {
        parent::__construct(403, $message);
    }
}
