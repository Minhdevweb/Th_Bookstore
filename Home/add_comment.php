<?php
header('Content-Type: application/json');
include "config.php";

// Tự động tạo bảng nếu chưa có
$conn->query("CREATE TABLE IF NOT EXISTS book_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,
    is_approved TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Tự động thêm các cột thiếu nếu bảng đã tồn tại (tương thích với bảng cũ)
$columns_to_check = [
    'is_approved' => "ALTER TABLE book_comments ADD COLUMN is_approved TINYINT(1) DEFAULT 1",
    'created_at' => "ALTER TABLE book_comments ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
];

foreach ($columns_to_check as $col => $sql) {
    $check = $conn->query("SHOW COLUMNS FROM book_comments LIKE '$col'");
    if ($check->num_rows == 0) {
        @$conn->query($sql); // @ để bỏ qua lỗi nếu cột đã tồn tại
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    exit;
}

$id = intval($_POST['product_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$content = trim($_POST['content'] ?? '');

if ($id <= 0 || empty($name) || empty($content)) {
    echo json_encode(["status" => "error", "message" => "Thiếu thông tin"]);
    exit;
}

// Bảo mật tốt hơn: dùng prepared statement
$stmt = $conn->prepare("INSERT INTO book_comments (product_id, name, content) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $id, $name, $content);

if ($stmt->execute()) {
    $new_id = $conn->insert_id;
    $time = date('d/m/Y H:i');
    echo json_encode([
        "status" => "success",
        "comment" => [
            "id" => $new_id,
            "name" => htmlspecialchars($name),
            "content" => nl2br(htmlspecialchars($content)),
            "created_at" => $time
        ]
    ]);
} else {
    error_log("Database error: " . $stmt->error); // Debug
    echo json_encode(["status" => "error", "message" => "Lỗi database: " . $stmt->error]);
}
$stmt->close();
?>