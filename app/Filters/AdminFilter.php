<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Traits\ApiResponseTrait;
use App\Models\UserModel;

class AdminFilter implements FilterInterface
{
    use ApiResponseTrait;

    public function before(RequestInterface $request, $arguments = null)
    {
        $user = $request->user;
        if ((int)$user->role !== UserModel::ROLE_ADMIN) {
            return $this->responseError(['message' => 'Admin only'], 403);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
