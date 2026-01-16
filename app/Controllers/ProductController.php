<?php

namespace App\Controllers;

use App\Models\ProductModel;
use App\Models\ProductVariantModel;
use App\Traits\ApiResponseTrait;
use App\Libraries\CloudinaryService;


class ProductController extends BaseController
{
    use ApiResponseTrait;

    protected $productModel;
    protected $variantModel;
    protected $cloudinary;
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->productModel = new ProductModel();
        $this->variantModel = new ProductVariantModel();
        $this->cloudinary = new CloudinaryService();
    }

    public function index()
    {
        try {
            $params = $this->request->getGet();
            $result = $this->productModel->getFilteredProducts($params);
            return $this->responseSuccess($result);
        } catch (\Exception $e) {
            return $this->responseError(['message' => $e->getMessage()]);
        }
    }

   public function detail($id = null)
    {
        if (!$id) {
            return $this->responseError(['message' => 'Product ID is required']);
        }

        $product = $this->productModel->select('products.id, products.name, image_url, description, category_id')->find($id);
        
        if (!$product) {
            return $this->responseError(['message' => 'Not found']);
        }
        $product['id'] = (int)$product['id'];
        
        $variants = $this->variantModel->select('id, color, size, price, stock')
                                    ->where('product_id', $id)
                                    ->findAll();

        foreach ($variants as &$variant) {
            $variant['id']    = (int)$variant['id'];
            $variant['price'] = (float)$variant['price'];
            $variant['stock'] = (int)$variant['stock'];
        }
        $product['total_stock'] = array_sum(array_column($variants, 'stock'));
        $product['variants'] = $variants;

        return $this->responseSuccess($product);
    }

    public function getbyTag()
    {
        $tag = $this->request->getVar('tag');
        if (empty($tag) || !in_array($tag, ['is_new', 'is_best_sell', 'is_featured'])) {
            return $this->responseError(['message' => 'Invalid tag']);
        }

        try {
            $products = $this->productModel->getProductsByTag($tag);
            return $this->responseSuccess($products);
        } catch (\Exception $e) {
            return $this->responseError(['message' => 'Failed to fetch products by tag']);
        }
    }

    public function related($id)
    {
        try {
            $limit =  $this->request->getVar('limit');
            $product = $this->productModel->find($id);
            if (!$product) {
                return $this->responseError(['message' => 'Product not found']);
            }
            $related = $this->productModel->getRelatedProducts($id, $product['category_id'], $limit);

            return $this->responseSuccess($related);
        } catch (\Exception $e) {
            return $this->responseError(['message' => $e->getMessage()]);
        }
    }

}
