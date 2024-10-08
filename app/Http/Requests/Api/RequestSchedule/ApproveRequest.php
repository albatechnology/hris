<?php

namespace App\Http\Requests\Api\RequestSchedule;

use App\Enums\ApprovalStatus;
use App\Traits\Requests\RequestToBoolean;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApproveRequest extends FormRequest
{
  use RequestToBoolean;

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
      'approved_by' => auth('sanctum')->id(),
      'approved_at' => now()
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
      'approval_status' => ['required', Rule::enum(ApprovalStatus::class)],
      'approved_by' => 'required',
      'approved_at' => 'required',
    ];
  }
}
