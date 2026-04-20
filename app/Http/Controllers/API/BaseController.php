<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;

class BaseController extends Controller
{
    protected function handleResponse($response)
    {
        if (!$response['status']) {
            return ApiResponse::error(
                $response['message'],
                $response['code'] ?? 400
            );
        }

        return ApiResponse::success(
            $response['data'] ?? null,
            $response['message']
        );
    }
}
