<?php

namespace App\Http\Requests\Api\PatrolLocation;

use Illuminate\Foundation\Http\FormRequest;

class AttendRequest extends FormRequest
{
  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
   */
  public function rules(): array
  {
    return [
      'patrol_location_id' => 'required|exists:patrol_locations,id',
      'lat' => 'required|string',
      'lng' => 'required|string',
    ];
  }
}
