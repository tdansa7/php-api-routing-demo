<?php
namespace App\Controllers;

class UserController {
    private $dataFile;
    private $users = [];
    
    public function __construct() {
        // データファイルのパスを設定
        $this->dataFile = dirname(__DIR__, 2) . '/data/users.json';
        
        // データディレクトリが存在しない場合は作成
        $dataDir = dirname($this->dataFile);
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0777, true);
        }
        
        // ユーザーデータを読み込み
        $this->loadUsers();
    }
    
    /**
     * JSONファイルからユーザーデータを読み込み
     */
    private function loadUsers() {
        if (file_exists($this->dataFile)) {
            $jsonContent = file_get_contents($this->dataFile);
            $data = json_decode($jsonContent, true);
            
            if (json_last_error() === JSON_ERROR_NONE && isset($data['users'])) {
                $this->users = $data['users'];
            } else {
                $this->initializeDataFile();
            }
        } else {
            $this->initializeDataFile();
        }
    }
    
    /**
     * データファイルを初期化
     */
    private function initializeDataFile() {
        $initialData = [
            'users' => [
                ['id' => 1, 'name' => '田中太郎', 'email' => 'tanaka@example.com', 'created_at' => date('Y-m-d H:i:s')],
                ['id' => 2, 'name' => '佐藤花子', 'email' => 'sato@example.com', 'created_at' => date('Y-m-d H:i:s')],
                ['id' => 3, 'name' => '鈴木一郎', 'email' => 'suzuki@example.com', 'created_at' => date('Y-m-d H:i:s')]
            ],
            'last_id' => 3,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $this->users = $initialData['users'];
        $this->saveToFile($initialData);
    }
    
    /**
     * データをJSONファイルに保存
     */
    private function saveUsers() {
        // 最大IDを取得
        $maxId = 0;
        foreach ($this->users as $user) {
            if ($user['id'] > $maxId) {
                $maxId = $user['id'];
            }
        }
        
        $data = [
            'users' => $this->users,
            'last_id' => $maxId,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $this->saveToFile($data);
    }
    
    /**
     * ファイルへの書き込み（ファイルロック付き）
     */
    private function saveToFile($data) {
        $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        // ファイルロックを使用して安全に書き込み
        $tempFile = $this->dataFile . '.tmp';
        
        if (file_put_contents($tempFile, $jsonContent, LOCK_EX) !== false) {
            // Windows環境でのファイル置換
            if (PHP_OS_FAMILY === 'Windows') {
                // Windowsではrenameの前に既存ファイルを削除
                if (file_exists($this->dataFile)) {
                    unlink($this->dataFile);
                }
            }
            rename($tempFile, $this->dataFile);
            return true;
        }
        
        return false;
    }
    
    /**
     * 次のIDを取得
     */
    private function getNextId() {
        $maxId = 0;
        foreach ($this->users as $user) {
            if ($user['id'] > $maxId) {
                $maxId = $user['id'];
            }
        }
        return $maxId + 1;
    }
    
    /**
     * ユーザー一覧を取得
     */
    public function index() {
        // 最新のデータを読み込み
        $this->loadUsers();
        
        // クエリパラメータで検索
        $search = $_GET['q'] ?? '';
        $filtered = $this->users;
        
        if ($search) {
            $filtered = array_filter($this->users, function($user) use ($search) {
                return stripos($user['name'], $search) !== false || 
                       stripos($user['email'], $search) !== false;
            });
            $filtered = array_values($filtered); // インデックスをリセット
        }
        
        // ソート（新しい順）
        usort($filtered, function($a, $b) {
            return $b['id'] - $a['id'];
        });
        
        return json_encode([
            'data' => $filtered,
            'total' => count($filtered),
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * 特定のユーザーを取得
     */
    public function show($id) {
        $this->loadUsers();
        
        foreach ($this->users as $user) {
            if ($user['id'] == $id) {
                return json_encode([
                    'data' => $user
                ], JSON_UNESCAPED_UNICODE);
            }
        }
        
        http_response_code(404);
        return json_encode([
            'error' => 'ユーザーが見つかりません'
        ], JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * 新規ユーザーを作成
     */
    public function store() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // バリデーション
        if (!isset($input['name']) || !isset($input['email'])) {
            http_response_code(400);
            return json_encode([
                'error' => '名前とメールアドレスは必須です'
            ], JSON_UNESCAPED_UNICODE);
        }
        
        // メールアドレスの重複チェック
        foreach ($this->users as $user) {
            if ($user['email'] === $input['email']) {
                http_response_code(400);
                return json_encode([
                    'error' => 'このメールアドレスは既に登録されています'
                ], JSON_UNESCAPED_UNICODE);
            }
        }
        
        // 新規ユーザー作成
        $newUser = [
            'id' => $this->getNextId(),
            'name' => $input['name'],
            'email' => $input['email'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // 電話番号などの追加フィールドがあれば保存
        if (isset($input['phone'])) {
            $newUser['phone'] = $input['phone'];
        }
        if (isset($input['address'])) {
            $newUser['address'] = $input['address'];
        }
        
        // ユーザーを追加して保存
        $this->users[] = $newUser;
        $this->saveUsers();
        
        http_response_code(201);
        return json_encode([
            'message' => 'ユーザーを作成しました',
            'data' => $newUser
        ], JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * ユーザー情報を更新
     */
    public function update($id) {
        $this->loadUsers();
        $input = json_decode(file_get_contents('php://input'), true);
        
        $userIndex = -1;
        foreach ($this->users as $index => $user) {
            if ($user['id'] == $id) {
                $userIndex = $index;
                break;
            }
        }
        
        if ($userIndex === -1) {
            http_response_code(404);
            return json_encode([
                'error' => 'ユーザーが見つかりません'
            ], JSON_UNESCAPED_UNICODE);
        }
        
        // メールアドレスの重複チェック（自分以外）
        if (isset($input['email'])) {
            foreach ($this->users as $index => $user) {
                if ($index !== $userIndex && $user['email'] === $input['email']) {
                    http_response_code(400);
                    return json_encode([
                        'error' => 'このメールアドレスは既に使用されています'
                    ], JSON_UNESCAPED_UNICODE);
                }
            }
        }
        
        // 更新処理
        if (isset($input['name'])) {
            $this->users[$userIndex]['name'] = $input['name'];
        }
        if (isset($input['email'])) {
            $this->users[$userIndex]['email'] = $input['email'];
        }
        if (isset($input['phone'])) {
            $this->users[$userIndex]['phone'] = $input['phone'];
        }
        if (isset($input['address'])) {
            $this->users[$userIndex]['address'] = $input['address'];
        }
        
        $this->users[$userIndex]['updated_at'] = date('Y-m-d H:i:s');
        
        // 保存
        $this->saveUsers();
        
        return json_encode([
            'message' => 'ユーザー情報を更新しました',
            'data' => $this->users[$userIndex]
        ], JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * ユーザーを削除
     */
    public function destroy($id) {
        $this->loadUsers();
        
        $userIndex = -1;
        foreach ($this->users as $index => $user) {
            if ($user['id'] == $id) {
                $userIndex = $index;
                break;
            }
        }
        
        if ($userIndex === -1) {
            http_response_code(404);
            return json_encode([
                'error' => 'ユーザーが見つかりません'
            ], JSON_UNESCAPED_UNICODE);
        }
        
        // ユーザーを削除
        $deletedUser = $this->users[$userIndex];
        array_splice($this->users, $userIndex, 1);
        
        // 保存
        $this->saveUsers();
        
        // 削除ログを別ファイルに保存（オプション）
        $this->logDeletion($deletedUser);
        
        http_response_code(204);
        return '';
    }
    
    /**
     * 削除ログを記録
     */
    private function logDeletion($user) {
        $logFile = dirname($this->dataFile) . '/deleted_users.log';
        $logEntry = date('Y-m-d H:i:s') . ' - Deleted: ' . json_encode($user, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * バックアップを作成
     */
    public function backup() {
        $backupDir = dirname($this->dataFile) . '/backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }
        
        $backupFile = $backupDir . '/users_' . date('Y-m-d_H-i-s') . '.json';
        copy($this->dataFile, $backupFile);
        
        return json_encode([
            'message' => 'バックアップを作成しました',
            'file' => basename($backupFile)
        ], JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * データをエクスポート（CSV形式）
     */
    public function export() {
        $this->loadUsers();
        
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="users_' . date('Y-m-d') . '.csv"');
        
        // BOM付きでExcelでの文字化けを防ぐ
        echo "\xEF\xBB\xBF";
        
        $output = fopen('php://output', 'w');
        
        // ヘッダー行
        fputcsv($output, ['ID', '名前', 'メールアドレス', '作成日時', '更新日時']);
        
        // データ行
        foreach ($this->users as $user) {
            fputcsv($output, [
                $user['id'],
                $user['name'],
                $user['email'],
                $user['created_at'] ?? '',
                $user['updated_at'] ?? ''
            ]);
        }
        
        fclose($output);
        exit;
    }
}