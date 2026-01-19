<?php

namespace App\Controllers\Admin;

use App\Models\OrderModel;
use App\Models\UserModel;
use App\Models\ProductModel;
use App\Traits\ApiResponseTrait;
use App\Controllers\BaseController;

class DashboardController extends BaseController
{
    use ApiResponseTrait;
    protected $orderModel;
    protected $userModel;
    protected $productModel;
    protected $db;

    public function __construct()
    {
        $this->orderModel = new OrderModel();
        $this->userModel = new UserModel();
        $this->productModel = new ProductModel();
        $this->db = \Config\Database::connect();
    }
 
    public function index()
    {
        try {
        $totalRevenue = $this->orderModel->where('status', OrderModel::STATUS_SHIPPED)
            ->selectSum('total_amount')
            ->first();

        $cards = [
            'total_revenue' => (float)($totalRevenue['total_amount'] ?? 0),
            'total_orders' => $this->orderModel->countAllResults(),
            'total_users' => $this->userModel->countAllResults(),
            'total_products' => $this->productModel->countAllResults(),
        ];

        $builder = $this->db->table('orders');

        $chartQuery = $builder->select('MONTH(created_at) as month, SUM(total_amount) as revenue')
            ->where('status', OrderModel::STATUS_SHIPPED)
            ->where('created_at >=', date('Y-m-d', strtotime('-6 months')))
            ->groupBy('MONTH(created_at)')
            ->orderBy('month', 'ASC')
            ->get()
            ->getResultArray();

        $chartData = $this->formatChartData($chartQuery);

        return $this->responseSuccess([
            'cards' => $cards,
            'chart' => $chartData
        ]);

        } catch (\Exception $e) {
            return $this->responseError($e->getMessage());
        }
    }

    private function formatChartData($data)
    {
        $resultData = [];

        for ($i = 5; $i >= 0; $i--) {
            $monthName = date('M', strtotime("-$i months"));
            $monthNum = (int)date('n', strtotime("-$i months"));

            $revenue = 0;
            foreach ($data as $row) {
                if ((int)$row['month'] == $monthNum) {
                    $revenue = (float)$row['revenue'];
                    break;
                }
            }

            $resultData[] = [
                'month'    => $monthName,
                'revenue' => $revenue
            ];
        }

        return $resultData;
    }
}
