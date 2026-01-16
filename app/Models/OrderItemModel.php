<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderItemModel extends Model
{
    protected $table            = 'order_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'order_id',
        'product_id',
        'variant_id',
        'product_name',
        'variant_info',
        'price',
        'quantity'
    ];
    protected $useTimestamps = false;


    public function getItemsByOrder($orderId)
    {
        return $this->where('order_id', $orderId)->findAll();
    }
}
