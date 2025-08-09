<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "db.php";

// HTTPメソッド取得
$method = $_SERVER["REQUEST_METHOD"];

switch ($method) {
    case "GET":
        // 全ユーザー取得
        $stmt = $pdo->query("SELECT id, name, email FROM users");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case "POST":
        // JSON入力を取得
        $input = json_decode(file_get_contents("php://input"), true);
        if (!isset($input["name"]) || !isset($input["email"])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing name or email"]);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO users (name, email) VALUES (:name, :email)");
        $stmt->execute([
            ":name" => $input["name"],
            ":email" => $input["email"]
        ]);

        echo json_encode(["message" => "User created", "id" => $pdo->lastInsertId()]);
        break;

    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(["error" => "Method not allowed"]);
}
