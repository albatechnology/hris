<?php

namespace App\View\Components\Datatables;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class DatatableAction extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public ?string $showRoute = null,
        public ?string $editRoute = null,
        public ?string $destroyRoute = null,
        public ?string $access = null,
    ) {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.datatables.datatable-action');
    }
}
