<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Traits\ApiResponseTrait;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthFilter implements FilterInterface
{
    use ApiResponseTrait;

    public function before(RequestInterface $request, $arguments = null)
    {
        $header = $request->getServer('HTTP_AUTHORIZATION');

        if (!$header) {
            return $this->responseError(['message' => 'Unauthorized access'], 401);
        }

        $token = str_replace('Bearer ', '', $header);

        try {
            $key = env('JWT_SECRET_KEY');
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            $request->user = $decoded;
        } catch (\Exception $e) {
            return $this->responseError(['message' => 'Unauthorized access'], 401);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
