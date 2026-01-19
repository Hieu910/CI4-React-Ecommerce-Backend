<?php

namespace App\Controllers;

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

    public function getProfile()
    {

        $userId = $this->request->user->uid;
        $user = $this->userModel->find($userId);

        if (!$user) {
            return $this->responseError(['message' => 'User not found'], 404);
        }

        $data = [
            'name'       => $user['name'],
            'email'      => $user['email'],
            'role'       => (int)$user['role'],
        ];

        return $this->responseSuccess($data);
    }

    public function updateProfile()
    {
        $userId = $this->request->user->uid;
        $name = $this->request->getVar("name");

        $rules = [
            'name' => 'required|min_length[3]',
        ];
        $messages = [
            'name' => [
                'required' => 'Name is required',
                'min_length' => 'Name must be at least 3 characters long'
            ]
        ];

        
        if (!$this->validate($rules, $messages)) {
                    $errors = $this->validator->getErrors();
                    $firstError = reset($errors);
                return $this->responseError(['message' => $firstError] );
                
             }


        if ($this->userModel->update($userId,  [
            'name' => $name,
        ])) {
            $updatedUser = $this->userModel->find($userId);
            unset($updatedUser['id']);
            unset($updatedUser['password']);
            $updatedUser['role'] = (int)$updatedUser['role'];
            return $this->responseSuccess($updatedUser);
        }

        return $this->responseError();
    }

    public function changePassword()
    {
        $userId = $this->request->user->uid;
        $input = $this->request->getJSON(true);

        $rules = [
            'old_password' => 'required',
            'new_password' => 'required|min_length[6]',

        ];

        $messages = [
            'old_password' => [
                'required' => 'Old password is required'
            ],
            'new_password' => [
                'required' => 'New password is required',
                'min_length' => 'New password must be at least 6 characters long'
            ]
        ];

        if (!$this->validate($rules, $messages)) {
                    $errors = $this->validator->getErrors();
                    $firstError = reset($errors);
                return $this->responseError(['message' => $firstError] );
                
             }

        $user = $this->userModel->find($userId);
        if (!password_verify($input['old_password'], $user['password'])) {
            return $this->responseError(['message' => 'Old password is incorrect']);
        }

        $newPasswordHash = password_hash($input['new_password'], PASSWORD_DEFAULT);

        if ($this->userModel->update($userId, ['password' => $newPasswordHash])) {
            return $this->responseSuccess();
        }

        return $this->responseError(['message' => 'Failed to change password']);
    }
}
