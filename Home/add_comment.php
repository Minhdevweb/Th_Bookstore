<?php
// Thêm comment
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0); // Tắt hiển thị lỗi để tránh output HTML
ini_set('log_errors', 1); // Vẫn log lỗi vào log file
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

// duyệt từng cột để kiểm tra
foreach ($columns_to_check as $col => $sql) {
    $check = $conn->query("SHOW COLUMNS FROM book_comments LIKE '$col'"); // kiểm tra cột
    if ($check->num_rows == 0) {
        @$conn->query($sql); // @ để bỏ qua lỗi nếu cột đã tồn tại
    }
}
// chỉ cho phép gửi POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    exit;
}

$id = intval($_POST['product_id'] ?? 0); // ép dữ liệu
$name = trim($_POST['name'] ?? ''); // trim loại bỏ khoảng trắng thừa
$content = trim($_POST['content'] ?? '');
// không cho phép để trống
if ($id <= 0 || empty($name) || empty($content)) {
    echo json_encode(["status" => "error", "message" => "Thiếu thông tin"]);
    exit;
}

// Bảo mật tốt hơn: dùng prepared statement -> chống sql ịnection 
$stmt = $conn->prepare("INSERT INTO book_comments (product_id, name, content) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $id, $name, $content); // iss -> int,string,string
// thực thi lệnh trên và kiểm tra kết quả
if ($stmt->execute()) {
    $new_id = $conn->insert_id; // lấy id bình luận vừa thêm 
    $time = date('d/m/Y H:i'); // định dạng thời gian
    // trả về dữ liệu bình luận mới thêm
    echo json_encode([
        "status" => "success",
        "comment" => [
            "id" => $new_id,
            "name" => htmlspecialchars($name),
            "content" => nl2br(htmlspecialchars($content)), // giữ nguyên xuống dòng
            "created_at" => $time // thời gian tạo
        ]
    ]);
} else {
    error_log("Database error: " . $stmt->error); // ghi lỗi vào log server -> debug
    echo json_encode(["status" => "error", "message" => "Lỗi database: " . $stmt->error]);
}
$stmt->close(); // đóng statement
?>