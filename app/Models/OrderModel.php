<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderModel extends Model
{
    protected $table            = 'orders';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['user_id', 'customer_name', 'total_amount', 'status'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    const STATUS_PENDING = 0;
    const STATUS_CONFIRM = 1;
    const STATUS_SHIPPED = 2;
    const STATUS_CANCELLED = 3;

    public static function getStatusLabel($statusId)
    {
        $labels = [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_CONFIRM => 'Confirmed',
            self::STATUS_SHIPPED => 'Shipped',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
        return $labels[$statusId] ?? 'Unknown';
    }
}
