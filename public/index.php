<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Router;
use App\Controllers\UserController;
use App\Controllers\ProductController;
use App\Middleware\CorsMiddleware;

// エラーハンドリング
error_reporting(E_ALL);
ini_set('display_errors', 0);

// CORSミドルウェア適用
CorsMiddleware::handle();

// ルーター初期化
$router = new Router();

// ルート定義
$router->get('/', function() {
    return json_encode([
        'message' => 'Welcome to PHP API Demo',
        'version' => '1.0.0',
        'endpoints' => [
            'GET /' => 'API情報',
            'GET /api/users' => 'ユーザー一覧',
            'GET /api/users/{id}' => 'ユーザー詳細',
            'POST /api/users' => 'ユーザー作成',
            'GET /api/products' => '商品一覧',
            'GET /api/health' => 'ヘルスチェック'
        ]
    ]);
});

// ヘルスチェック
$router->get('/api/health', function() {
    return json_encode([
        'status' => 'healthy',
        'timestamp' => date('Y-m-d H:i:s'),
        'environment' => $_ENV['APP_ENV'] ?? 'production'
    ]);
});

// ユーザー関連エンドポイント
$userController = new UserController();
$router->get('/api/users', [$userController, 'index']);
$router->get('/api/users/{id}', [$userController, 'show']);
$router->post('/api/users', [$userController, 'store']);
$router->put('/api/users/{id}', [$userController, 'update']);
$router->delete('/api/users/{id}', [$userController, 'destroy']);
// バックアップ機能
$router->post('/api/users/backup', [$userController, 'backup']);

// CSV エクスポート機能
$router->get('/api/users/export', [$userController, 'export']);

// 商品関連エンドポイント
$productController = new ProductController();
$router->get('/api/products', [$productController, 'index']);
$router->get('/api/products/{id}', [$productController, 'show']);

// 管理画面ルート（index.phpに追加）
$router->get('/admin', function() {
    include __DIR__ . '/admin.html';
    exit;
});

// リクエスト処理
$router->dispatch();

