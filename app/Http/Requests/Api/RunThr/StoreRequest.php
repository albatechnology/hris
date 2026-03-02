<?php

namespace App\Http\Requests\Api\RunThr;

use App\Models\User;
use App\Rules\CompanyTenantedRule;
use App\Traits\Requests\RequestToBoolean;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    use RequestToBoolean;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['required', new CompanyTenantedRule()],
            // 'period' => ['required', 'string', function (string $attr, string $value, Closure $fail) {
            //     // $runThr = RunThr::where('period', $value)->exists();
            //     // if ($runThr) {
            //     //     $fail('Cannot run payroll in the same period');
            //     // }
            // }],
            'thr_date' => 'required|date',
            'payment_date' => 'required|date',
            'user_ids' => [
                'required',
                'string',
                function (string $attr, string $value, Closure $fail) {
                    collect(explode(',', $value))->each(function ($id) use ($fail) {
                        $user = User::tenanted()->select('id', 'resign_date')->firstWHere('id', $id);

                        if (!$user) {
                            $fail('The selected user ids is invalid (' . $id . ')');
                        }
                    });
                },
            ],
        ];
    }
}
