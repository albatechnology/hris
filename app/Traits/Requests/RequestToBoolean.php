<?php

namespace App\Traits\Requests;

trait RequestToBoolean
{
    /**
     * Convert to boolean
     */
    public function toBoolean($booleable): bool
    {
        return filter_var($booleable, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
}
