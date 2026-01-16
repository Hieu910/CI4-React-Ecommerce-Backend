<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Traits\ApiResponseTrait;
use App\Models\OrderModel;
use App\Models\ProductModel;
use App\Models\OrderItemModel;
use App\Models\UserModel;


class OrderController extends BaseController
{
    use ApiResponseTrait;
    protected $orderModel;
    protected $productModel;
    protected $orderItemModel;

    public function __construct()
    {
        $this->productModel = new ProductModel();
        $this->orderModel = new OrderModel();
        $this->orderItemModel = new OrderItemModel();
    }
    public function index()
    {

        $orders = $this->orderModel->select('id, customer_name, total_amount, status as status_code, created_at')->orderBy('created_at', 'DESC')->findAll();

        foreach ($orders as &$order) {
            $order['total_amount'] = (float) number_format($order['total_amount'], 2);
            $order['status_label'] = $this->orderModel->getStatusLabel($order['status_code']);
            $order['created_at'] = date('d/m/Y H:i', strtotime($order['created_at']));
        }

        return $this->responseSuccess($orders);
    }


    public function updateStatus($id = null)
    {   
        if ($this->request->user->role !== UserModel::ROLE_ADMIN) {
            return $this->responseError(['message' => 'Unauthorized']);
        }
        $order = $this->orderModel->find($id);
        if (!$order) {
            return $this->responseError(['message' => 'Order not found']);
        }

        $newStatus = (int)$this->request->getVar('status');

        if ($this->orderModel->update($id, ['status' => $newStatus])) {
            return $this->responseSuccess();
        }

        return $this->responseError(['message' => 'Failed to update status']);
    }

    public function show($id = null)
    {

        $order = $this->orderModel->select('id, customer_name, total_amount, status, created_at')->find($id);
        if (!$order) {
            return $this->responseError(['message' => 'Order not found']);
        }

        $order['total_amount'] = (float) number_format($order['total_amount'], 2);
        $order['created_at'] = date('d/m/Y H:i', strtotime($order['created_at']));


        $items = $this->orderItemModel->select('id, product_name, product_id, variant_info, price, quantity')->where('order_id', $id)->findAll();
        foreach ($items as &$item) {
            $item['price'] = (float) ($item['price']);
            $product = $this->productModel->find($item['product_id']);
            $item['image_url'] = $product['image_url'] ?? '';
            unset($item['product_id']);
        }
        return $this->responseSuccess([
            ...$order,
            'items' => $items
        ]);
    }
}
