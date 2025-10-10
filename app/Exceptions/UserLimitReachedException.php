<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class UserLimitReachedException extends HttpException
{
    public function __construct(string $message = "You have reached the maximum number of users")
    {
        parent::__construct(403, $message);
    }
}
