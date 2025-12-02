<?php
header('Content-Type: application/json');
include "config.php";
include "session.php";

// Yêu cầu đăng nhập
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Tạo đơn hàng mới
    $user_id = $_SESSION['user_id'];
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    
    // Lấy thông tin sản phẩm để tính giá
    $stmt = $conn->prepare("SELECT price FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $stmt->bind_result($price);
    $stmt->fetch();
    $stmt->close();

    $total = $price * $quantity;
    
    // Thêm vào bảng orders
    $stmt = $conn->prepare("INSERT INTO orders (user_id, product_id, quantity, total, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->bind_param("iidd", $user_id, $product_id, $quantity, $total);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Order placed successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to place order"]);
    }
    $stmt->close();
} else {
    // GET request - Lấy danh sách đơn hàng
    $user_id = $_SESSION['user_id'];
    $is_admin = isAdmin();
    
    // Admin xem được tất cả đơn hàng, user chỉ xem đơn của mình
    if ($is_admin) {
        $sql = "SELECT o.*, p.title, u.email FROM orders o 
                JOIN products p ON o.product_id = p.id 
                JOIN users u ON o.user_id = u.id 
                ORDER BY o.created_at DESC";
        $stmt = $conn->prepare($sql);
    } else {
        $sql = "SELECT o.*, p.title FROM orders o 
                JOIN products p ON o.product_id = p.id 
                WHERE o.user_id = ? 
                ORDER BY o.created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = [];
    
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    
    echo json_encode($orders);
    $stmt->close();
}

$conn->close();
?>