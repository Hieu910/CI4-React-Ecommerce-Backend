<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Traits\ApiResponseTrait;


class UserController extends BaseController
{
    use ApiResponseTrait;
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function index()
    {
        $users = $this->userModel
            ->select('id, name, email, role, created_at')
            ->findAll();

        foreach ($users as &$user) {
            $user['role_label'] = UserModel::getRoleLabel($user['role']);
        }

        return $this->responseSuccess($users);
    }

    public function update($id)
    {
        if ($this->request->user->role !== UserModel::ROLE_ADMIN) {
            return $this->responseError(['message' => 'Unauthorized']);
        }

        $newRole = $this->request->getVar('role');

        if ($id == $this->request->user->uid && $newRole != UserModel::ROLE_ADMIN) {
            return $this->responseError(['message' => 'Admin cannot change their own role']);
        }

        $this->userModel->update($id, ['role' => $newRole]);

        return $this->responseSuccess();
    }
}
