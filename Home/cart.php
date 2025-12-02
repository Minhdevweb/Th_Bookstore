<?php
// Start session if not active (other files may start it in config/session)
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
header('Content-Type: application/json');
include "config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(["status" => "error", "message" => "Please login first"]);
        exit;
    }

    $action = $_POST['action'];
    
    // --- Helpers ---
    function getProductStock(mysqli $conn, int $productId): ?int {
        $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $stmt->bind_result($stock);
        $ok = $stmt->fetch();
        $stmt->close();
        if (!$ok) return null;
        return (int)$stock;
    }

    function ensureQuantityWithinStock(mysqli $conn, int $productId, int $desiredQty): array {
        $stock = getProductStock($conn, $productId);
        if ($stock === null) return [false, "Sản phẩm không tồn tại"];
        if ($stock <= 0) return [false, "Sản phẩm đã hết hàng"];
        if ($desiredQty > $stock) return [false, "Số lượng vượt quá tồn kho (còn ${stock})"];
        return [true, null];
    }

    switch($action) {
        case 'add':
            $product_id = intval($_POST['product_id']);
            $quantity = intval($_POST['quantity'] ?? 1);
            if ($quantity < 1) $quantity = 1;
            
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            
            // Nếu sản phẩm đã có trong giỏ thì tăng số lượng
            $currentQty = isset($_SESSION['cart'][$product_id]) ? intval($_SESSION['cart'][$product_id]) : 0;
            $newQty = $currentQty + $quantity;
            [$ok, $msg] = ensureQuantityWithinStock($conn, $product_id, $newQty);
            if (!$ok) { echo json_encode(["status"=>"error","message"=>$msg]); exit; }
            $_SESSION['cart'][$product_id] = $newQty;
            
            // return updated cart summary
            $summary = [];
            foreach ($_SESSION['cart'] as $pid => $qty) {
                $summary[$pid] = $qty;
            }
            echo json_encode(["status" => "success", "message" => "Added to cart", "totalItems" => array_sum($_SESSION['cart']), "cart" => $summary]);
            break;
            
        case 'update':
            $product_id = intval($_POST['product_id']);
            $quantity = intval($_POST['quantity']);
            if ($quantity < 1) $quantity = 1;
            [$ok, $msg] = ensureQuantityWithinStock($conn, $product_id, $quantity);
            if (!$ok) { echo json_encode(["status"=>"error","message"=>$msg]); exit; }

            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id] = $quantity;
            }

            echo json_encode(["status" => "success", "message" => "Cart updated", "totalItems" => array_sum($_SESSION['cart'])]);
            break;
            
        case 'remove':
            $product_id = $_POST['product_id'];
            
            if (isset($_SESSION['cart'][$product_id])) {
                unset($_SESSION['cart'][$product_id]);
            }
            
            echo json_encode(["status" => "success", "message" => "Item removed", "totalItems" => array_sum($_SESSION['cart'])]);
            break;
            
        case 'clear':
            $_SESSION['cart'] = [];
            echo json_encode(["status" => "success", "message" => "Cart cleared", "totalItems" => 0]);
            break;
    }
} else {
    // GET request - Lấy thông tin giỏ hàng
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        echo json_encode(["status" => "success", "items" => [], "totalItems" => 0]);
        exit;
    }

    $cart = [];
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        $stmt = $conn->prepare("SELECT id, title, price, image FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($product = $result->fetch_assoc()) {
            $product['quantity'] = $quantity;
            $product['total'] = $quantity * $product['price'];
            $cart[] = $product;
        }

        $stmt->close();
    }

    echo json_encode(["status" => "success", "items" => $cart, "totalItems" => array_sum($_SESSION['cart'])]);
}

$conn->close();
?>