<?php
namespace App\Controllers;

class UserController {
    // ダミーデータ（実際はデータベースから取得）
    private $users = [
        ['id' => 1, 'name' => '田中太郎', 'email' => 'tanaka@example.com'],
        ['id' => 2, 'name' => '佐藤花子', 'email' => 'sato@example.com'],
        ['id' => 3, 'name' => '鈴木一郎', 'email' => 'suzuki@example.com']
    ];
    
    public function index() {
        return json_encode([
            'data' => $this->users,
            'total' => count($this->users)
        ]);
    }
    
    public function show($id) {
        foreach ($this->users as $user) {
            if ($user['id'] == $id) {
                return json_encode(['data' => $user]);
            }
        }
        
        http_response_code(404);
        return json_encode(['error' => 'User not found']);
    }
    
    public function store() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['name']) || !isset($input['email'])) {
            http_response_code(400);
            return json_encode(['error' => 'Name and email are required']);
        }
        
        $newUser = [
            'id' => count($this->users) + 1,
            'name' => $input['name'],
            'email' => $input['email']
        ];
        
        http_response_code(201);
        return json_encode([
            'message' => 'User created successfully',
            'data' => $newUser
        ]);
    }
    
    public function update($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        foreach ($this->users as &$user) {
            if ($user['id'] == $id) {
                if (isset($input['name'])) $user['name'] = $input['name'];
                if (isset($input['email'])) $user['email'] = $input['email'];
                
                return json_encode([
                    'message' => 'User updated successfully',
                    'data' => $user
                ]);
            }
        }
        
        http_response_code(404);
        return json_encode(['error' => 'User not found']);
    }
    
    public function destroy($id) {
        foreach ($this->users as $index => $user) {
            if ($user['id'] == $id) {
                http_response_code(204);
                return '';
            }
        }
        
        http_response_code(404);
        return json_encode(['error' => 'User not found']);
    }
}