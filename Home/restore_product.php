<?php
header('Content-Type: application/json');
include "config.php";

// kích hoạt lại sản phẩm đã xóa

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = intval($_POST['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode(["status" => "error", "message" => "Invalid product ID"]);
        exit;
    }

    $conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1");

    $stmt = $conn->prepare("UPDATE products SET is_active = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Khôi phục sản phẩm thành công."]);
    } else {
        echo json_encode(["status" => "error", "message" => $stmt->error]);
    }
    $stmt->close();
}
$conn->close();
?>

