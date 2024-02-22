<?php

namespace App\Classes;

class Permission
{
    public function __construct(
        public bool $create = false,
        public bool $read = false,
        public bool $delete = false,
        public bool $update = false,
    ) {
    }
}
