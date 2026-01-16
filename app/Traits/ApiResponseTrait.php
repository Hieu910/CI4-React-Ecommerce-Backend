<?php

namespace App\Traits;

trait ApiResponseTrait
{

    public function apiResponse($data = null, $status = 200, $code = 0)
    {
        return service('response')->setStatusCode($status)->setJSON([
            'code' => $code,
            'data' => $data
        ]);
    }

    public function responseSuccess($data = null, $statusCode = 200)
    {
        return $this->apiResponse($data, $statusCode, 0);
    }

    public function responseError($data = null, $statusCode = 400, $code = 1)
    {
        return $this->apiResponse($data, $statusCode, $code);
    }
}
