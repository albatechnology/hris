<?php

namespace App\Interfaces\Services\User;

use App\Http\Requests\Api\User\RegisterRequest;
use App\Interfaces\Services\BaseServiceInterface;
use App\Models\User;

interface UserServiceInterface extends BaseServiceInterface
{
    public function register(RegisterRequest $request): User;
}
