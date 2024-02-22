<?php

namespace App\View\Components\Forms;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;

class SelectArray extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public Model $model,
        public string $name,
        public string $label,
        public $options,
        public mixed $value = null,
        public ?string $required = null,
        public ?string $helper = null,
        public ?string $placeholder = null,
        public ?string $class = null,
    ) {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.forms.select-array');
    }
}
