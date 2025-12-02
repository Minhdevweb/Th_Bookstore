<?php
header('Content-Type: application/json');
include "config.php";
include "session.php";

// Chỉ admin mới có quyền cập nhật trạng thái đơn hàng
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'];
    $status = $_POST['status']; // pending, confirmed, shipping, delivered, cancelled
    
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $order_id);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Order status updated"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update order status"]);
    }
    
    $stmt->close();
}

$conn->close();
?>