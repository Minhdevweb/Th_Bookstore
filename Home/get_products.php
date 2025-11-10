<?php
header('Content-Type: application/json');
include "config.php";

// --- Cấu hình số sản phẩm mỗi trang ---
$limit = 6; // mỗi trang 6 sản phẩm

// --- Lấy số trang hiện tại ---
if (isset($_GET['page']) && is_numeric($_GET['page'])) {
    $page = (int)$_GET['page'];
} else {
    $page = 1;
}

if ($page < 1) $page = 1;

// --- Tính vị trí bắt đầu lấy dữ liệu ---
$start = ($page - 1) * $limit;

// --- Lấy tổng số sản phẩm ---
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1");
$totalQuery = $conn->query("SELECT COUNT(*) AS total FROM products WHERE is_active = 1");
$totalData = $totalQuery->fetch_assoc();
$totalProducts = $totalData['total'];
$totalPages = ceil($totalProducts / $limit);

// --- Lấy sản phẩm cho trang hiện tại ---
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS stock INT DEFAULT 0");
$sql = "SELECT * FROM products WHERE is_active = 1 ORDER BY id DESC LIMIT $start, $limit";
$result = $conn->query($sql);

$products = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {

        // Kiểm tra và chỉnh đường dẫn ảnh cho đúng
        $img = trim($row['image']);
        if ($img != "" && !str_starts_with($img, "http") && !str_starts_with($img, "../uploads/")) {
            $img = "../uploads/" . basename($img);
        }
        $row['image'] = $img;

        $products[] = $row;
    }
}

// --- Trả dữ liệu về cho frontend ---
echo json_encode([
    "status" => "success",
    "currentPage" => $page,
    "totalPages" => $totalPages,
    "products" => $products
]);

$conn->close();
?>
