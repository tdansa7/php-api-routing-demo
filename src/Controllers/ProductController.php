<?php
namespace App\Controllers;

class ProductController {
    private $products = [
        ['id' => 1, 'name' => 'ノートPC', 'price' => 98000, 'stock' => 15],
        ['id' => 2, 'name' => 'マウス', 'price' => 2500, 'stock' => 50],
        ['id' => 3, 'name' => 'キーボード', 'price' => 8500, 'stock' => 30]
    ];
    
    public function index() {
        $query = $_GET['q'] ?? '';
        $filtered = $this->products;
        
        if ($query) {
            $filtered = array_filter($this->products, function($product) use ($query) {
                return stripos($product['name'], $query) !== false;
            });
        }
        
        return json_encode([
            'data' => array_values($filtered),
            'total' => count($filtered)
        ]);
    }
    
    public function show($id) {
        foreach ($this->products as $product) {
            if ($product['id'] == $id) {
                return json_encode(['data' => $product]);
            }
        }
        
        http_response_code(404);
        return json_encode(['error' => 'Product not found']);
    }
}