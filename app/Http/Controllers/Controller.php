<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    const CREATED_MESSAGE = 'Data created successfully';
    const UPDATED_MESSAGE = 'Data updated successfully';
    const DELETED_MESSAGE = 'Data deleted successfully';

    protected function jsonResponse($status, $data)
    {
        $code = $status == 'success' ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST;

        return response()->json([
            'meta' => [
                'code' => $code,
                'status' => $status,
            ],
            'data' => $data,
        ], $code);
    }
}
