<?php

namespace App\Interfaces\Services\GuestBook;

use App\Interfaces\Services\BaseServiceInterface;
use Illuminate\Support\Collection;

interface GuestBookServiceInterface extends BaseServiceInterface
{
    public function export(array $filters): Collection;
}