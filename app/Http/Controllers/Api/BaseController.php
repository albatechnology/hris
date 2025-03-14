<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class BaseController extends Controller
{
    protected int $per_page;

    public function __construct()
    {
        $perPage = (int) request()->per_page;
        $this->per_page = $perPage > 0 ? $perPage : 20;
    }

    public function createdResponse(string $message = 'Data created successfully')
    {
        return response()->json(['message' => $message]);
    }

    public function updatedResponse(string $message = 'Data updated successfully')
    {
        return response()->json(['message' => $message], 202);
    }

    public function deletedResponse()
    {
        return response()->json(['message' => 'Data deleted successfully']);
    }

    public function errorResponse(string $message, array $data = [], int $code = 500)
    {
        return response()->json(['message' => $message, ...$data], $code);
    }
}
