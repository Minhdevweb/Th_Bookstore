<?php
header('Content-Type: application/json');
include "config.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = intval($_POST['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode(["status" => "error", "message" => "Invalid product ID"]);
        exit;
    }

    // ẩn sản phẩm thay vì xóa nếu nó vẫn nằm trong orders
    $conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1");

    // kiểm tra sản phẩm có đc tham chiếu đến bảng orders không
    $orderStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM orders WHERE product_id = ?");
    $orderStmt->bind_param("i", $id);
    $orderStmt->execute();
    $orderResult = $orderStmt->get_result();
    $orderCount = $orderResult ? (int)$orderResult->fetch_assoc()['cnt'] : 0;
    $orderStmt->close();

    if ($orderCount > 0) {
        $softStmt = $conn->prepare("UPDATE products SET is_active = 0 WHERE id = ?");
        $softStmt->bind_param("i", $id);
        if ($softStmt->execute()) {
            echo json_encode([
                "status" => "success",
                "message" => "Sản phẩm đã được ẩn để bảo toàn lịch sử đơn hàng."
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => $softStmt->error]);
        }
        $softStmt->close();
        exit;
    }

    // chỉ lấy đường dẫn hình ảnh để xóa file
    $imgStmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $imgStmt->bind_param("i", $id);
    $imgStmt->execute();
    $imgResult = $imgStmt->get_result();
    $imagePath = null;
    if ($imgResult && $imgResult->num_rows > 0) {
        $row = $imgResult->fetch_assoc();
        $imagePath = $row['image'] ?? null;
    }
    $imgStmt->close();

    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        if ($imagePath && file_exists($imagePath)) {
            unlink($imagePath);
        }
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => $stmt->error]);
    }

    $stmt->close();
}
$conn->close();
?>
