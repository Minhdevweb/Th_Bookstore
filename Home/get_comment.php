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

$id = intval($_GET['product_id'] ?? 0);
if ($id <= 0) {
    echo json_encode([]);
    exit;
}

// Kiểm tra xem có cột is_approved không
$check_approved = $conn->query("SHOW COLUMNS FROM book_comments LIKE 'is_approved'");
$has_approved = $check_approved->num_rows > 0;

// DÙNG PREPARED STATEMENT - điều chỉnh query dựa trên cấu trúc bảng
if ($has_approved) {
    $stmt = $conn->prepare("SELECT * FROM book_comments WHERE product_id = ? AND is_approved = 1 ORDER BY created_at DESC");
} else {
    $stmt = $conn->prepare("SELECT * FROM book_comments WHERE product_id = ? ORDER BY created_at DESC");
}
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$comments = [];

while ($c = $result->fetch_assoc()) {
    // Xử lý created_at nếu có, nếu không thì dùng thời gian hiện tại
    $created_at = isset($c['created_at']) && !empty($c['created_at']) 
        ? date('d/m/Y H:i', strtotime($c['created_at'])) 
        : date('d/m/Y H:i');
    
    $comments[] = [
        "name" => htmlspecialchars($c['name']),
        "content" => nl2br(htmlspecialchars($c['content'])),
        "created_at" => $created_at
    ];
}

echo json_encode($comments);
$stmt->close();
?>