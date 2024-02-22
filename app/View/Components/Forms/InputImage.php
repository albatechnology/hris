<?php

namespace App\View\Components\Forms;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;
use Illuminate\View\View;

class InputImage extends Component
{
    public function __construct(
        public Model $model,
        public string $name,
        public string $label,
        public ?string $helper = null,
        public ?string $required = null,
        public ?string $class = null,
    ) {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.forms.input-image');
    }
}
