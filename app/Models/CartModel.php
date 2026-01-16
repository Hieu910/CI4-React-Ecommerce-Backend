<?php

namespace App\Models;

use CodeIgniter\Model;

class CartModel extends Model
{
    protected $table            = 'carts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = ['user_id', 'product_id', 'variant_id', 'quantity'];

    protected $useTimestamps    = true;
    protected $dateFormat       = 'datetime';
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    public function getItemsByUserId($userId)
    {
        return $this->select('
            carts.*, 
            products.name as name, 
            products.image_url as product_image,
            product_variants.size, 
            product_variants.color, 
            product_variants.price as price
        ')
            ->join('products', 'products.id = carts.product_id')
            ->join('product_variants', 'product_variants.id = carts.variant_id')
            ->where('carts.user_id', $userId)
            ->findAll();
    }

    public function addToCart($userId, $productId, $variantId, $quantity)
    {
        $existing = $this->where([
            'user_id'    => $userId,
            'product_id' => $productId,
            'variant_id' => $variantId
        ])->first();

        if ($existing) {
            return $this->update($existing['id'], [
                'quantity' => $existing['quantity'] + $quantity
            ]);
        } else {
            return $this->insert([
                'user_id'    => $userId,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity'   => $quantity
            ]);
        }
    }
}
