<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class BaseController extends Controller
{
    const SUCCESS_SESSION_KEY = 'success';
    const INFO_SESSION_KEY = 'info';
    const WARNING_SESSION_KEY = 'warning';
    const ERROR_SESSION_KEY = 'error';
    const CREATED_MESSAGE = 'Data created successfully';
    const UPDATED_MESSAGE = 'Data updated successfully';
    const DELETED_MESSAGE = 'Data deleted successfully';
    const FORCE_DELETED_MESSAGE = 'Data force deleted successfully';
    const RESTORED_MESSAGE = 'Data restored successfully';

    protected int $per_page = 15;

    public function __construct()
    {
        // $perPage = (int) request()->per_page;
        // $this->per_page = $perPage > 0 ? $perPage : 20;
        $this->per_page = min(request('per_page', $this->per_page), 100);
    }

    public function okResponse(string $message = 'OK')
    {
        return response()->json(['message' => $message]);
    }

    protected function createdResponse(string $message = self::CREATED_MESSAGE)
    {
        return response()->json(['message' => $message], 201);
    }

    protected function updatedResponse(string $message = self::UPDATED_MESSAGE)
    {
        return response()->json(['message' => $message], 200);
    }

    protected function deletedResponse(string $message = self::DELETED_MESSAGE)
    {
        return response()->json(['message' => $message], 200);
    }

    protected function forceDeletedResponse(string $message = self::FORCE_DELETED_MESSAGE)
    {
        return response()->json(['message' => $message], 200);
    }

    protected function restoredResponse(string $message = self::RESTORED_MESSAGE)
    {
        return response()->json(['message' => $message], 200);
    }

    public function errorResponse(string $message, array $data = [], int $code = 500)
    {
        return response()->json(['message' => $message, ...$data], $code);
    }
}
