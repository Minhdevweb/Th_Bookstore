<?php
include "config.php";
include "session.php";
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: checkout.php');
  exit;
}

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
if ($name === '' || $phone === '' || $address === '') {
  header('Location: checkout.php');
  exit;
}

// Ensure schema has needed columns
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS customer_name VARCHAR(100) NULL");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS customer_phone VARCHAR(30) NULL");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS customer_address VARCHAR(255) NULL");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS stock INT DEFAULT 0");

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
  header('Location: index.php');
  exit;
}

$user_id = $_SESSION['user_id'];

// --- Helper functions ---
function fetchProductInfo(mysqli $conn, int $productId): ?array {
  $stmt = $conn->prepare("SELECT title, price, stock FROM products WHERE id = ?");
  $stmt->bind_param("i", $productId);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res->fetch_assoc();
  $stmt->close();
  return $row ?: null;
}

function decrementStockIfEnough(mysqli $conn, int $productId, int $qty): bool {
  $stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
  $stmt->bind_param("iii", $qty, $productId, $qty);
  $stmt->execute();
  $ok = ($stmt->affected_rows === 1);
  $stmt->close();
  return $ok;
}

function createOrder(mysqli $conn, int $userId, int $productId, int $qty, float $price, string $name, string $phone, string $address): void {
  $total = $price * $qty;
  $ins = $conn->prepare("INSERT INTO orders (user_id, product_id, quantity, total, status, customer_name, customer_phone, customer_address) VALUES (?, ?, ?, ?, 'pending', ?, ?, ?)");
  $ins->bind_param("iiidsss", $userId, $productId, $qty, $total, $name, $phone, $address);
  $ins->execute();
  $ins->close();
}

function alertAndRedirect(string $message, string $target = 'index.php'): void {
  echo '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><title>Đặt hàng</title></head><body>';
  echo "<script>alert(" . json_encode($message) . "); window.location.href = " . json_encode($target) . ";</script>";
  echo '</body></html>';
}

// Place orders per item with stock validation and atomic decrement
$failed = [];
foreach ($_SESSION['cart'] as $product_id => $quantity) {
  $product_id = (int)$product_id;
  $quantity = (int)$quantity;
  if ($quantity < 1) { continue; }

  $info = fetchProductInfo($conn, $product_id);
  if (!$info) { $failed[] = (string)$product_id; continue; }
  $title = $info['title'] ?? (string)$product_id;
  $price = (float)($info['price'] ?? 0);
  $stock = (int)($info['stock'] ?? 0);
  if ($stock < $quantity) { $failed[] = $title; continue; }

  if (!decrementStockIfEnough($conn, $product_id, $quantity)) { $failed[] = $title; continue; }
  createOrder($conn, $user_id, $product_id, $quantity, $price, $name, $phone, $address);
  unset($_SESSION['cart'][$product_id]);
}

// If some items failed
if (!empty($failed)) {
  $list = implode(', ', $failed);
  alertAndRedirect('Một số sản phẩm đã hết hàng hoặc không đủ số lượng: ' . $list . '. Vui lòng điều chỉnh giỏ hàng.');
  exit;
}

// All succeeded: clear cart and notify
$_SESSION['cart'] = [];

alertAndRedirect('Đặt hàng thành công! Cảm ơn bạn đã mua sắm.');
exit;
?>


