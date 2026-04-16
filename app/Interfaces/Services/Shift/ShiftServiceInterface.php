<?php

namespace App\Interfaces\Services\Shift;

use App\Interfaces\Services\BaseServiceInterface;

interface ShiftServiceInterface extends BaseServiceInterface
{
    public function reportShiftUsers(array $filters, ?string $export = null);
    public function importShiftUsers($file);
}