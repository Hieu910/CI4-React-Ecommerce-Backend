<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    const ROLE_USER = 0;
    const ROLE_ADMIN = 1;

    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['name', 'email', 'password', 'role'];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];


    public function getByEmail($email = null)
    {
        return $this->select(['id', 'name', 'email', 'role', 'password'])->where('email', $email)->first();
    }

    public static function getRoleLabel($roleId)
    {
        $labels = [
            self::ROLE_USER  => 'User',
            self::ROLE_ADMIN => 'Admin',
        ];
        return $labels[$roleId] ?? 'Unknown';
    }
}
