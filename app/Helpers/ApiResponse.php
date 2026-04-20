<?php

namespace App\Helpers;

class ApiResponse
{
    public static function success($data = null, $message = 'Success', $code = 200, $meta = null)
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
            'meta' => $meta
        ], $code);
    }

    public static function error($message = 'Error', $code = 400, $data = null)
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'data' => $data
        ], $code);
    }
}