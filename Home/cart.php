<?php
// XỬ LÝ GIỎ HÀNG
// Khởi động session nếu chưa được khởi động
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0); // Tắt hiển thị lỗi để tránh output HTML
ini_set('log_errors', 1); // Vẫn log lỗi vào log file
include "config.php";
// Chỉ xử lý khi request là POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(["status" => "error", "message" => "Please login first"]);
        exit;
    }
// Lấy hành động từ POST (add, update, remove, clear)
    $action = $_POST['action'];
    
    // --- Helpers ---
    // * Lấy số lượng tồn kho hiện tại của một sản phẩm
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
    // * Kiểm tra xem số lượng yêu cầu có vượt quá tồn kho không
    function ensureQuantityWithinStock(mysqli $conn, int $productId, int $desiredQty): array {
        $stock = getProductStock($conn, $productId);
        if ($stock === null) return [false, "Sản phẩm không tồn tại"];
        if ($stock <= 0) return [false, "Sản phẩm đã hết hàng"];
        if ($desiredQty > $stock) return [false, "Số lượng vượt quá tồn kho (còn $stock)"];
        return [true, null];
    }
    // --- Xử lý các hành động ---
    switch($action) {
        // Thêm sản phẩm vào giỏ hàng
        case 'add':
            $product_id = intval($_POST['product_id']);
            $quantity = intval($_POST['quantity'] ?? 1);
            if ($quantity < 1) $quantity = 1; // tối thiểu 1
            // Khởi tạo giỏ hàng nếu chưa có
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            
            // Nếu sản phẩm đã có trong giỏ thì tăng số lượng
            $currentQty = isset($_SESSION['cart'][$product_id]) ? intval($_SESSION['cart'][$product_id]) : 0;
            $newQty = $currentQty + $quantity;// Tính số lượng mới = cũ + mới thêm
            [$ok, $msg] = ensureQuantityWithinStock($conn, $product_id, $newQty);
            if (!$ok) { echo json_encode(["status"=>"error","message"=>$msg]); exit; }
            $_SESSION['cart'][$product_id] = $newQty;// Cập nhật giỏ hàng
            
            // Trả về thông tin tóm tắt giỏ hàng
            $summary = [];
            foreach ($_SESSION['cart'] as $pid => $qty) {// Chỉ gửi ID => số lượng
                $summary[$pid] = $qty;
            }
            echo json_encode(["status" => "success", 
            "message" => "Added to cart", 
            "totalItems" => array_sum($_SESSION['cart']), 
            "cart" => $summary]);
            break;
            // Cập nhật số lượng một sản phẩm trong giỏ
        case 'update':
            $product_id = intval($_POST['product_id']);
            $quantity = intval($_POST['quantity']);
            if ($quantity < 1) $quantity = 1;
            // Kiểm tra tồn kho với số lượng mới
            [$ok, $msg] = ensureQuantityWithinStock($conn, $product_id, $quantity);
            if (!$ok) { echo json_encode(["status"=>"error",
                "message"=>$msg]); 
                exit;
             }
             // Chỉ cập nhật nếu sản phẩm đang có trong giỏ
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id] = $quantity;
            }

            echo json_encode(["status" => "success", 
            "message" => "Cart updated", 
            "totalItems" => array_sum($_SESSION['cart'])]);
            break;
            // Xóa một sản phẩm khỏi giỏ hàng
        case 'remove':
            $product_id = $_POST['product_id'];
            
            if (isset($_SESSION['cart'][$product_id])) {
                unset($_SESSION['cart'][$product_id]);
            }
            
            echo json_encode([
                "status" => "success", 
                "message" => "Item removed", 
                "totalItems" => array_sum($_SESSION['cart'])]);
            break;
            // Xóa toàn bộ giỏ hàng
        case 'clear':
            $_SESSION['cart'] = [];
            echo json_encode([
                "status" => "success",
                 "message" => "Cart cleared",
                  "totalItems" => 0]);
            break;
    }
} else {
  // --- Xử lý GET request: Lấy thông tin chi tiết giỏ hàng để hiển thị ---
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        echo json_encode(["status" => "success", "items" => [], "totalItems" => 0]);
        exit;
    }

    $cart = [];
    // Duyệt từng sản phẩm trong giỏ hàng để lấy thông tin chi tiết từ DB
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        $stmt = $conn->prepare("SELECT id, title, price, image FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($product = $result->fetch_assoc()) {
            // Thêm thông tin số lượng và thành tiền
            $product['quantity'] = $quantity;
            $product['total'] = $quantity * $product['price'];
            $cart[] = $product;
        }

        $stmt->close();
    }
    // Trả về danh sách sản phẩm chi tiết trong giỏ hàng
    echo json_encode(["status" => "success", "items" => $cart, "totalItems" => array_sum($_SESSION['cart'])]);
}

$conn->close();
?>