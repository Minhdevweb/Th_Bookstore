<?php
// Lấy tất cả sản phẩm
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0); // Tắt hiển thị lỗi để tránh output HTML
ini_set('log_errors', 1); // Vẫn log lỗi vào log file
include "config.php";

// Đảm bảo cột is_active và stock tồn tại (giống get_products.php)
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1");
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS stock INT DEFAULT 0");

$sql = "SELECT * FROM products WHERE is_active = 1 ORDER BY id DESC";
$result = $conn->query($sql);

$products = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $img = trim($row['image'] ?? '');
        if ($img !== "" && !str_starts_with($img, "http") && !str_starts_with($img, "../uploads/")) {
            $img = "../uploads/" . basename($img);
        }
        $row['image'] = $img;
        $products[] = $row;
    }
}

echo json_encode([
    "status" => "success",
    "products" => $products
]);

$conn->close();
?>


