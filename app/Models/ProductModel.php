<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table            = 'products';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['category_id', 'name', 'description', 'image_url', 'image_public_id', 'is_new', 'is_best_sell', 'is_featured'];

    const CAT_TSHIRTS    = 'T-SHIRTS';
    const CAT_PANTS      = 'PANTS';
    const CAT_DRESSES    = 'DRESSES';
    const CAT_JACKETS    = 'JACKETS';
    const CAT_ACCESSORIES = 'ACCESSORIES';

    public static $validCategories = [
        self::CAT_TSHIRTS,
        self::CAT_PANTS,
        self::CAT_DRESSES,
        self::CAT_JACKETS,
        self::CAT_ACCESSORIES
    ];

    public function getByCategory($code, $limit = 8)
    {
        if (!in_array(strtoupper($code), self::$validCategories)) {
            throw new \Exception("Invalid category code");
        }

        return $this->select('products.*')
            ->join('categories', 'categories.id = products.category_id')
            ->where('categories.code', strtoupper($code))
            ->findAll($limit);
    }

    public function getFilteredProducts($filters = [])
    {

        $defaults = [
            'search'       => '',
            'categories'   => [],
            'sizes'        => [],
            'min_price'    => null,
            'max_price'    => null,
            'stock_status' => 'all',
            'sort_by'      => 'name',
            'sort_order'   => 'ASC',
            'page'         => 1,
        ];

        $params = [...$defaults, ...$filters];

        $builder = $this->db->table('products p');

        $builder->select('
            p.id, 
            p.name, 
            c.id as category_id,
            p.image_url, 
            p.description,
            p.is_new as isNew,
            p.is_best_sell as isBestSell,
            p.is_featured as isFeatured,
            SUM(v.stock) as total_stock, 
            MIN(v.price) as min_price, 
            MAX(v.price) as max_price,
        ');
        $builder->join('categories c', 'c.id = p.category_id', 'left');
        $builder->join('product_variants v', 'v.product_id = p.id');
        $builder->groupBy('p.id');

        if (!empty($params['search'])) {
            $searchTerm = trim($params['search']);
            $builder->groupStart()->like('p.name', $searchTerm)
                ->orLike('p.id', $searchTerm)
                ->groupEnd();
        }

        if (!empty($params['categories'])) {
            $categoriesArray = is_array($params['categories']) ? $params['categories'] : explode(',', $params['categories']);
            $builder->whereIn('c.id',  $categoriesArray);
        }

        if (!empty($params['sizes'])) {
            $sizesArray = is_array($params['sizes']) ? $params['sizes'] : explode(',', $params['sizes']);
            $builder->whereIn('v.size',  $sizesArray);
        }

        if (isset($params['min_price'])) $builder->having('min_price >=', $params['min_price']);
        if (isset($params['max_price'])) $builder->having('max_price <=', $params['max_price']);


        if ($params['stock_status'] === 'in_stock') {
            $builder->having('total_stock >', 0);
        } elseif ($params['stock_status'] === 'out_of_stock') {
            $builder->having('total_stock <=', 0);
        }

        $sortOrder = strtoupper($params['sort_order']) === 'DESC' ? 'DESC' : 'ASC';
        if ($params['sort_by'] === 'price') {
            $builder->orderBy('min_price', $sortOrder);
        } else {
            $builder->orderBy('p.name', $sortOrder);
        }

        $totalItems = $builder->countAllResults(false);

        $page = (int)($params['page'] ?? 1);
        $perPage = 8;
        $offset = ($page - 1) * $perPage;

        $builder->limit($perPage, $offset);

        $results = $builder->get()->getResultArray();

        foreach ($results as &$item) {
            $item['id']          = (int)$item['id'];
            $item['total_stock'] = (int)($item['total_stock'] ?? 0);
            $item['min_price']   = (float)($item['min_price'] ?? 0);
            $item['max_price']   = (float)($item['max_price'] ?? 0);
        }

        return [
            'items'  => $results,
            'total' => $totalItems
        ];
    }

    public function getProductsByTag($tag, $limit = 8)
    {
        $builder = $this->db->table('products p');
        $builder->select('
            p.id, 
            p.name, 
            p.category_id,
            p.image_url, 
            SUM(v.stock) as total_stock, 
            MIN(v.price) as min_price, 
            MAX(v.price) as max_price,
        ');
        $builder->join('product_variants v', 'v.product_id = p.id');
        $builder->groupBy('p.id');
        $builder->limit($limit);
        $builder->where("p.$tag", 1);
        $builder->orderBy('p.created_at', 'DESC');

        $results = $builder->get()->getResultArray();

        foreach ($results as &$item) {
            $item['id']          = (int)$item['id'];
            $item['total_stock'] = (int)($item['total_stock'] ?? 0);
            $item['min_price']   = (float)($item['min_price'] ?? 0);
            $item['max_price']   = (float)($item['max_price'] ?? 0);
        }

        return $results;
    }

    public function getRelatedProducts($productId, $categoryId, $limit = 4)
    {
        $builder = $this->db->table('products p');
        $builder->select('
        p.id, 
        p.name, 
        p.image_url, 
        SUM(v.stock) as total_stock,
        MIN(v.price) as min_price,
        MAX(v.price) as max_price,
    ');
        $builder->join('product_variants v', 'v.product_id = p.id', 'left');
        $builder->where('p.category_id', $categoryId);
        $builder->where('p.id !=', $productId);
        $builder->groupBy('p.id');
        $builder->orderBy('p.id', 'RANDOM');
        $builder->limit($limit);
        $results = $builder->get()->getResultArray();

        foreach ($results as &$item) {
            $item['id']          = (int)$item['id'];
            $item['total_stock'] = (int)($item['total_stock'] ?? 0);
            $item['min_price']   = (float)($item['min_price'] ?? 0);
            $item['max_price']   = (float)($item['max_price'] ?? 0);
        }

        return $results;
    }
}
