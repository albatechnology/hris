<?php

namespace App\Http\Requests\Payment;

use App\Models\Subscription;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Prepare inputs for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'subscription_id' => Subscription::select('id')->where('group_id', $this->group_id)->firstOrFail()->id,
            'payment_at' => date('Y-m-d H:i:s', strtotime($this->payment_at)),
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
            'subscription_id' => 'required|integer',
            'active_end_date' => 'required|date',
            'payment_at' => 'required|date_format:Y-m-d H:i:s',
            'total_price' => 'required|numeric',
        ];
    }
}
