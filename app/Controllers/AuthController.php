<?php

namespace App\Controllers;

use App\Models\UserModel;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Traits\ApiResponseTrait;

class AuthController extends BaseController
{
    use ApiResponseTrait;

    protected $userModel;
    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function register()
    {
        try {
            $input = $this->request->getVar();
            $rules = [
                'name'     => 'required',
                'email'    => 'required|valid_email|is_unique[users.email]',
                'password' => 'required|min_length[6]',
                'confirm_password' => 'required|matches[password]'
            ];
            $messages = [
                'name' => [
                    'required' => 'Name is required'
                ],
                'email' => [
                    'required'    => 'Email is required',
                    'valid_email' => 'Email is not valid',
                    'is_unique'   => 'Email already exists'
                ],
                'password' => [
                    'required'   => 'Password is required',
                    'min_length' => 'Password must be at least 6 characters long'
                ],
                'confirm_password' => [
                    'required'   => 'Confirm password is required',
                    'matches'    => 'Confirm password must match password'
                ]
            ];

             if (!$this->validate($rules, $messages)) {
                    $errors = $this->validator->getErrors();
                    $firstError = reset($errors);
                return $this->responseError(['message' => $firstError] );
                
             }

           


            $data = [
                'name'     => trim($this->request->getVar('name')),
                'email'    => trim($this->request->getVar('email')),
                'password' => password_hash($this->request->getVar('password'), PASSWORD_DEFAULT),
                'role'     => UserModel::ROLE_USER
            ];
            if ($this->userModel->save($data)) {
                return $this->responseSuccess();
            }
            return $this->responseError();
        } catch (\Exception $e) {
            return $this->responseError(['message' => 'An error occurred during registration']);
        }
    }


    public function login()
    {
        $email    = $this->request->getVar('email');
        $password = $this->request->getVar('password');
        $user  = $this->userModel->getByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            return $this->responseError(['message' => 'Email or password is incorrect']);
        }

        $tokens = $this->_generateTokens($user);
        set_cookie(
            'refresh_token',
            $tokens['refresh_token'],
            604800,
            '',
            '/',
            '',
            false,
            true
        );
        unset($user['id']);
        unset($user['password']);


        return $this->responseSuccess([
            'user'          => $user,
            'access_token'  => $tokens['access_token'],
        ]);
    }

    public function logout()
    {
        delete_cookie('refresh_token');
        return $this->responseSuccess();
    }

    public function refresh()
    {
        $refreshToken = $this->request->getCookie('refresh_token');

        if (!$refreshToken) {
            return $this->responseError(['message' => 'Login session expired'], 401);
        }

        try {
            $key = env('JWT_SECRET_KEY');
            $decoded = JWT::decode($refreshToken, new Key($key, 'HS256'));
            $user = $this->userModel->find($decoded->uid);

            $newTokens = $this->_generateTokens($user);

            set_cookie(
                'refresh_token',
                $newTokens['refresh_token'],
                604800,
                '',
                '/',
                '',
                false,
                true
            );

            return $this->responseSuccess([
                'access_token' => $newTokens['access_token']
            ]);
        } catch (\Exception $e) {
            return $this->responseError(['message' => 'Invalid token'], 401);
        }
    }

    private function _generateTokens($user)
    {
        $key = env('JWT_SECRET_KEY');;
        $time = time();
        $payload = [
            'iss'  => 'localhost',
            'iat'  => $time,
            'uid'  => $user['id'],
            'role' => $user['role']
        ];

        $payload['exp'] = $time + 3600;
        $accessToken = JWT::encode($payload, $key, 'HS256');

        $payload['exp'] = $time + 604800;
        $refreshToken = JWT::encode($payload, $key, 'HS256');

        return [
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken
        ];
    }
}
