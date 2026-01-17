<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\CategoryModel;
use App\Traits\ApiResponseTrait;

class CategoryController extends BaseController
{
    use ApiResponseTrait;

    protected $categoryModel;

    public function __construct()
    {
        $this->categoryModel = new CategoryModel();
    }
    public function index()
    {
        try {
            $data = $this->categoryModel->orderBy('id', 'ASC')->findAll();
            return $this->responseSuccess($data);
        }
        catch (\Exception $e) {
            return $this->responseError(['message' => $e->getMessage()]);
        }
    }
}
