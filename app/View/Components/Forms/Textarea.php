<?php

namespace App\View\Components\Forms;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;

class Textarea extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        Model $model,
        public string $name,
        public string $label,
        public mixed $value = null,
        public ?string $helper = null,
        public ?string $placeholder = null,
        public ?string $required = null,
        public ?string $class = null,
    ) {
        // model is provided for edit
        if ($model) {
            $this->value = $value ?? $model->$name;
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.forms.textarea');
    }
}
