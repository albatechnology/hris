<?php

namespace App\Http\Requests\Api\PatrolLocation;

use Illuminate\Foundation\Http\FormRequest;

class ScanQrCodeRequest extends FormRequest
{
  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
   */
  public function rules(): array
  {
    return [
      'patrol_location_id' => 'nullable|exists:patrol_locations,id',
      'token' => 'required|string',
      'lat' => 'nullable|string',
      'lng' => 'nullable|string',
    ];
  }
}
