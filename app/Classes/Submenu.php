<?php

namespace App\Classes;

class Submenu
{
    public function __construct(
        public string $permission,
        public string $url,
        public string $icon,
        public string $title,
        public bool $disabled = false,
    ) {
    }
}
