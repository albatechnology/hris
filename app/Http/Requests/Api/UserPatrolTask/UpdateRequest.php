<?php

namespace App\Http\Requests\Api\UserPatrolTask;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'patrol_task_id' => 'required|exists:patrol_tasks,id',
            'description' => 'required|string',
        ];
    }
}
