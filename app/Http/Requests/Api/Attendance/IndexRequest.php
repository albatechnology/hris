<?php

namespace App\Http\Requests\Api\Attendance;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class IndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare inputs for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'filter' => [
                ...($this->filter ?? []),
                // 'month' => !empty($this->filter['month']) ? date('m', strtotime(sprintf('2024-%s-01', $this->filter['month']))) : date('m'),
                // 'year' => !empty($this->filter['year']) ? date('Y', strtotime(sprintf('%s-01-01', $this->filter['year']))) : date('Y'),
            ],
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'filter' => 'nullable|array',
            'filter.user_id' => ['nullable', function ($attribute, $value, \Closure $fail) {
                if ($value) {
                    $userLogin = auth('sanctum')->user();

                    if (!$userLogin->is_super_admin) {
                        $user = User::where('id', $value)->firstOrFail(['id', 'parent_id', '_lft', '_rgt']);

                        if ($user->id === $userLogin->id) return;

                        if ($user->type->is(UserType::USER) && DB::table('user_supervisors')->where('user_id', $value)->where('supervisor_id', $user->id)->doesntExist()) {
                            $fail("User is not your descendant.");
                        }
                        // if (!$user->isDescendantOf($userLogin)) {
                        //     $fail("User is not your descendant.");
                        // }
                    }
                }
            }],
            'filter.month' => 'nullable|date_format:m',
            'filter.year' => 'nullable|date_format:Y',
            'sort' => 'nullable|string',
            'include' => 'nullable|string',
        ];
    }
}
