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

    public function deletedResponse()
    {
        return response()->json(['message' => 'Data deleted successfully']);
    }
}
