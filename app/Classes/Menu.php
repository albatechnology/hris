<?php

namespace App\Classes;

class Menu
{
    // public $submenus;
    public function __construct(
        public string $permission,
        public string $icon,
        public string $title,
        Submenu ...$submenus
    ) {
        $this->submenus = collect($submenus ?? []);
    }

    public function getAllSubmenuRoutes(): array
    {
        $paths = $this->submenus->map(function (Submenu $submenu) {
            return $submenu->url.'*';
        });

        return $paths->all();
    }
}
