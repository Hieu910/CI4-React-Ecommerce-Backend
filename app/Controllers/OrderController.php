<?php

namespace App\Controllers;

use App\Models\OrderModel;
use App\Models\OrderItemModel;
use App\Models\CartModel;
use App\Models\UserModel;
use App\Models\ProductVariantModel;
use App\Models\ProductModel;
use App\Traits\ApiResponseTrait;

class OrderController extends BaseController
{
    use ApiResponseTrait;

    protected $orderModel;
    protected $orderItemModel;
    protected $cartModel;
    protected $variantModel;
    protected $userModel;
    protected $productModel;
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->orderModel = new OrderModel();
        $this->orderItemModel = new OrderItemModel();
        $this->cartModel = new CartModel();
        $this->variantModel = new ProductVariantModel();
        $this->userModel = new UserModel();
        $this->productModel = new ProductModel();
    }

    public function checkout()
    {
        $userId = $this->request->user->uid;

        $user = $this->userModel->find($userId);
        $customerName = $user['name'];
        $cartItems = $this->cartModel->getItemsByUserId($userId);

        if (empty($cartItems)) {
            return $this->responseError(['message' => 'Cart is empty']);
        }

       $this->db->transStart();

        try {
            $totalAmount = 0;
            
            foreach ($cartItems as &$item) {
                $variant = $this->variantModel->find($item['variant_id']);

                if (!$variant) {
                    throw new \Exception("Product variant not found");
                }

                if ($variant['stock'] < $item['quantity']) {
                    throw new \Exception("Not enough stock available");
                }
                $totalAmount += $variant['price'] * $item['quantity'];
                $item['current_stock'] = $variant['stock'];
            }

            $orderData = [
                'user_id'       => $userId,
                'customer_name' => $customerName,
                'total_amount'   => $totalAmount,
                'status'        => OrderModel::STATUS_PENDING,
            ];
            $orderId = $this->orderModel->insert($orderData);

            foreach ($cartItems as $item) {
                $this->orderItemModel->insert([
                    'order_id'     => $orderId,
                    'product_id'   => $item['product_id'],
                    'variant_id'   => $item['variant_id'],
                    'product_name' => $item['name'],
                    'variant_info' => $item['color'] . " - " . $item['size'],
                    'price'        => $item['price'],
                    'quantity'     => $item['quantity']
                ]);

                $this->variantModel->update($item['variant_id'], [
                    'stock' => $item['current_stock'] - $item['quantity']
                ]);
            }

            $this->cartModel->where('user_id', $userId)->delete();

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return $this->responseError(['message' => 'Checkout failed']);
            }

            return $this->responseSuccess();
        } catch (\Exception $e) {
            $this->db->transRollback();
            return $this->responseError($e->getMessage());
        }
    }

    public function index()
    {
        $userId = $this->request->user->uid;
        $orders = $this->orderModel->select('id, customer_name, total_amount, status as status_code, created_at')->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->findAll();

        foreach ($orders as &$order) {
            $order['status_label'] = OrderModel::getStatusLabel($order['status_code']);
            $order['total_amount'] = (float) number_format($order['total_amount'], 2);
            $order['created_at'] = date('d/m/Y H:i', strtotime($order['created_at']));
            unset($order['updated_at']);
            unset($order['user_id']);
        }

        return $this->responseSuccess($orders);
    }

    public function show($id)
    {
        $userId = $this->request->user->uid;
        $order = $this->orderModel->where(['id' => $id, 'user_id' => $userId])->first();
        if (!$order) {
            return $this->responseError(['message' => 'Order not found']);
        }
        unset($order['user_id']);
        $order['status_label'] = OrderModel::getStatusLabel($order['status']);
        $order['total_amount'] = (float) number_format($order['total_amount'], 2);
        $order['created_at'] = date('d/m/Y H:i', strtotime($order['created_at']));

        $items = $this->orderItemModel->select('id, product_name as name, product_id, variant_info, price, quantity')->where('order_id', $id)->findAll();
        foreach ($items as &$item) {
            $item['price'] = (float) ($item['price']);
            $product = $this->productModel->find($item['product_id']);
            $item['image_url'] = $product['image_url'] ?? '';
            unset($item['product_id']);
        }

        return $this->responseSuccess([
            ...$order,
            'items'  => $items
        ]);
    }

 
}
