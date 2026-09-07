<?php

namespace App\Traits;

trait ApiResponse {
    public function success($data = null, $message = 'Success', $code = 200) {
        return response()->json(['status' => true, 'message' => $message, 'data' => $data], $code);
    }

    public function error($message = 'Error', $code = 400) {
        return response()->json(['status' => false, 'message' => $message], $code);
    }
}