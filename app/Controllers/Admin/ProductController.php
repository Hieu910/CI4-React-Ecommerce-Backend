<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
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

    public function getVariants($id)
    {
        try {
            $variants = $this->variantModel->where('product_id', $id)->findAll();
            foreach ($variants as &$variant) {
                $variant['id'] = (int)$variant['id'];
                $variant['stock'] = (int)$variant['stock'];
                $variant['price'] = (float)$variant['price'];
                $variant['product_id'] = (int)$variant['product_id'];
            }
            return $this->responseSuccess($variants);
        } catch (\Exception $e) {
            return $this->responseError(['message' => $e->getMessage()]);
        }
    }

    public function create()
    {

        $name = $this->request->getVar('name');
        $category = $this->request->getVar('category');
        $isNew = $this->request->getVar('isNew');
        $isBestSell = $this->request->getVar('isBestSell');
        $isFeatured = $this->request->getVar('isFeatured');
        $description = $this->request->getVar('description');
        $variantsRaw = $this->request->getVar('variants');
        $variants = json_decode($variantsRaw, true);
        $imageUrl = $this->request->getVar('image_url'); // Nhận link ảnh nếu dán URL
        $file = $this->request->getFile('image');

        if (empty($variants)) {
            return $this->responseError([
                'message' => 'At least one variant is required'
            ]);
        }

        $cloudinaryUrl = null;
        $imagePublicId = null;
        try {
            if ($file && $file->isValid()) {
                $result = $this->cloudinary->upload($file->getTempName());
                $imagePublicId = $result['public_id'];
                $cloudinaryUrl = $result['secure_url'];
            } elseif (!empty($imageUrl)) {
                $result  = $this->cloudinary->upload($imageUrl);
                $imagePublicId = $result['public_id'];
                $cloudinaryUrl = $result['secure_url'];
            }
        } catch (\Exception $e) {
            return $this->responseError([
                'message' => $e->getMessage()
            ]);
        }

        $this->db->transStart();

        try {

            $productId = $this->productModel->insert([
                'name' => $name,
                'category_id' => $category,
                'description' => $description,
                'image_url' => $cloudinaryUrl,
                'image_public_id' => $imagePublicId,
                'is_new' => $isNew,
                'is_best_sell' => $isBestSell,
                'is_featured' => $isFeatured
            ]);

            if (!$productId) {
                throw new \Exception();
            }

            if (!empty($variants) && is_array($variants)) {
                foreach ($variants as $v) {
                    $color = $v['color'] ?? null;
                    $size  = $v['size'] ?? null;
                    $price = (float)($v['price'] ?? 0);
                    $addStock = (int)($v['stock'] ?? 0);

                    $this->variantModel->insert([
                        'product_id' => $productId,
                        'color'      => $color,
                        'size'       => $size,
                        'price'      => $price,
                        'stock'      => $addStock
                    ]);
                    
                }   
            }

            $this->db->transComplete();

            return $this->responseSuccess([
                'message' => 'Create Success',
            ]);
        } catch (\Exception $e) {
            $this->db->transRollback();
            return $this->responseError(['message' => 'Fail to create product']);
        }
    }

    public function update($id = null)
    {
        if (!$id) {
            return $this->responseError(['message' => 'Product ID is required']);
        }
        $product = $this->productModel->find($id);
        if (!$product) {
            return $this->responseError([['message' => 'Product not found']]);
        }

        $imageUrl = $this->request->getVar('image_url');
        $file = $this->request->getFile('image');
        $cloudinaryUrl =  $product['image_url'];
        $imagePulicId = $product['image_public_id'];
        try {
            if ($file && $file->isValid()) {
                $result = $this->cloudinary->upload($file->getTempName());
                $imagePulicId = $result['public_id'];
                $cloudinaryUrl = $result['secure_url'];
            } elseif (!empty($imageUrl && $imageUrl !== $product['image_url'])) {
                $result = $this->cloudinary->upload($file->getTempName());
                $imagePulicId = $result['public_id'];
                $cloudinaryUrl = $result['secure_url'];
            }
        } catch (\Exception $e) {
            return $this->responseError([
                'message' => 'Image Invalid'
            ]);
        }

        try {
            $this->db->transStart();
            $this->productModel->update($id, [
                'name'      => $this->request->getVar('name'),
                'image_url' => $cloudinaryUrl,
                'image_public_id' => $imagePulicId,
                'category_id'  => (int) $this->request->getVar('category'),
                'description' => $this->request->getVar('description'),
                'is_new'       => (int) $this->request->getVar('isNew'),    
                'is_best_sell' => (int) $this->request->getVar('isBestSell'), 
                'is_featured'  => (int) $this->request->getVar('isFeatured'),
            ]);

            $variantsRaw = $this->request->getVar('variants');
            $variants = json_decode($variantsRaw, true);
            $keptIds = [];
            if (!empty($variants)) {
                foreach ($variants as $v) {
                    $vId = $v['id'] ?? null;
                    $color = $v['color'] ?? null;
                    $size  = $v['size'] ?? null;
                    $newStock = (int)($v['stock'] ?? 0);

                    $existing = $this->variantModel->find($vId);

                    if ($existing) {
                        $this->variantModel->where('id', $vId)->update(null, [
                            'color' => $color,
                            'size'  => $size,
                            'stock' => (int)$newStock,  
                            'price' => $v['price'] ?? $existing['price']
                        ]);
                        $keptIds[] = $vId;
                    } else {
                       $newId = $this->variantModel->insert([
                            'product_id' => $id,
                            'color'      => $color,
                            'size'       => $size,
                            'price'      => $v['price'] ?? 0,
                            'stock'      => $newStock
                        ]);
                        $keptIds[] = $newId;
                    }
                }
                $this->variantModel->where('product_id', $id)
                   ->whereNotIn('id', $keptIds)
                   ->delete();
            }

            $this->db->transComplete();

            return $this->responseSuccess([
                'message' => 'Update Success'
            ]);
        } catch (\Exception $e) {
            $this->db->transRollback();
            return $this->responseError(['message' => $e->getMessage()]);
        }
    }

  public function delete($id = null)
    {
        if (!$id) {
            return $this->responseError(['message' => 'Product ID is required']);
        }

        $product = $this->productModel->find($id);
        if ($product) {
            try {
                $this->db->transStart();
                if (!empty($product['image_url'] && isset($product['image_public_id']))) {
                   
                    $this->cloudinary->destroy($product['image_public_id']); 
                    
                }

                $this->variantModel->where('product_id', $id)->delete();
                $this->productModel->delete($id);

                $this->db->transComplete();

                if ($this->db->transStatus() === false) {
                    return $this->responseError(['message' => 'Product delete failed']);
                }

                return $this->responseSuccess(['message' => 'Product deleted successfully']);
                
            } catch (\Exception $e) {
                return $this->responseError(['message' => 'Error: ' . $e->getMessage()]);
            }
        }

        return $this->responseError(['message' => 'Product not found']);
    }
}