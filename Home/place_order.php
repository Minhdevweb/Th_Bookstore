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
$payment_method = trim($_POST['payment_method'] ?? 'bank_transfer');
// Validate payment method
if (!in_array($payment_method, ['bank_transfer', 'cod'])) {
  $payment_method = 'bank_transfer';
}
if ($name === '' || $phone === '' || $address === '') {
  header('Location: checkout.php');
  exit;
}

// Ensure schema has needed columns
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS customer_name VARCHAR(100) NULL");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS customer_phone VARCHAR(30) NULL");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS customer_address VARCHAR(255) NULL");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) NULL DEFAULT 'bank_transfer'");
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

function createOrder(mysqli $conn, int $userId, int $productId, int $qty, float $price, string $name, string $phone, string $address, string $paymentMethod): void {
  $total = $price * $qty;
  $ins = $conn->prepare("INSERT INTO orders (user_id, product_id, quantity, total, status, customer_name, customer_phone, customer_address, payment_method) VALUES (?, ?, ?, ?, 'pending', ?, ?, ?, ?)");
  $ins->bind_param("iiidssss", $userId, $productId, $qty, $total, $name, $phone, $address, $paymentMethod);
  $ins->execute();
  $ins->close();
}

function alertAndRedirect(string $message, string $target = 'index.php'): void {
  echo '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><title>Đặt hàng</title></head><body>';
  echo "<script>alert(" . json_encode($message) . "); window.location.href = " . json_encode($target) . ";</script>";
  echo '</body></html>';
}

// Determine selected items (if provided), else process all cart
$selectedIds = array_map('intval', $_POST['selected'] ?? []);
$cartToProcess = [];
if (!empty($selectedIds)) {
  foreach ($selectedIds as $pid) {
    if (isset($_SESSION['cart'][$pid])) {
      $cartToProcess[$pid] = (int)$_SESSION['cart'][$pid];
    }
  }
} else {
  $cartToProcess = $_SESSION['cart'];
}

// Place orders per selected item with stock validation and atomic decrement
$failed = [];
foreach ($cartToProcess as $product_id => $quantity) {
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
  createOrder($conn, $user_id, $product_id, $quantity, $price, $name, $phone, $address, $payment_method);
  // remove only processed items from cart
  unset($_SESSION['cart'][$product_id]);
}

// If some items failed
if (!empty($failed)) {
  $list = implode(', ', $failed);
  alertAndRedirect('Một số sản phẩm đã hết hàng hoặc không đủ số lượng: ' . $list . '. Vui lòng điều chỉnh giỏ hàng.');
  exit;
}

// All succeeded for selected items: keep any unselected items in cart

alertAndRedirect('Đặt hàng thành công! Cảm ơn bạn đã mua sắm.');
exit;
?>


