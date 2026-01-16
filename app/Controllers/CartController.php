<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\CartModel;
use App\Models\ProductVariantModel;
use App\Traits\ApiResponseTrait;

class CartController extends BaseController
{
    use ApiResponseTrait;

    protected $cartModel;
    protected $variantModel;

    public function __construct()
    {
        $this->cartModel = new CartModel();
        $this->variantModel = new ProductVariantModel();
    }
    public function index()
    {
        $userId = $this->request->user->uid;

        $items = $this->cartModel->select('
            carts.id as cart_id,
            carts.quantity,
            products.name,
            products.image_url,
            products.id as product_id,
            product_variants.id as variant_id,
            product_variants.size,
            product_variants.color,
            product_variants.price,
        ')
            ->join('products', 'products.id = carts.product_id')
            ->join('product_variants', 'product_variants.id = carts.variant_id')
            ->where('carts.user_id', $userId)
            ->findAll();

        $totalAmount = 0;
        foreach ($items as &$item) {
            $item['price'] = (float)$item['price'];
            $totalAmount +=  (float)$item['price'] * (int)$item['quantity'];
        }

        return $this->responseSuccess([
            'items' => $items,
            'total_amount' => $totalAmount
        ]);
    }
    public function saveToCart()
    {
        $userId    = $this->request->user->uid;
        $variantId = $this->request->getVar('variant_id');
        $quantity  = (int)$this->request->getVar('quantity') ?: 1;

        $variant = $this->variantModel->find($variantId);

        if (!$variant) {
            return $this->responseError(['message' => 'Product not available']);
        }

        if ($variant['stock'] < $quantity) {
            return $this->responseError(['message' => 'Insufficient stock available']);
        }

        $productId = $variant['product_id'];

        $existing = $this->cartModel->where([
            'user_id'    => $userId,
            'variant_id' => $variantId
        ])->first();

        if ($existing) {
            $newQty = $quantity;

            if ($variant['stock'] < $newQty) {
                return $this->responseError(['message' => 'Insufficient stock available']);
            }

            $this->cartModel->update($existing['id'], ['quantity' => $newQty]);
            return $this->responseSuccess();
        }

        $data = [
            'user_id'    => $userId,
            'product_id' => $productId,
            'variant_id' => $variantId,
            'quantity'   => $quantity
        ];

        $this->cartModel->insert($data);
        return $this->responseSuccess();
    }

    public function delete($id)
    {
        $userId = $this->request->user->uid;
        $this->cartModel->where('id', $id)->where('user_id', $userId)->delete();
        return $this->responseSuccess();
    }
}
