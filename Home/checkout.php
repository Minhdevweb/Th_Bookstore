<?php
include "config.php";
include "session.php";
requireLogin();

// Build cart details from session
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
  header('Location: index.php');
  exit;
}

$items = [];
$subtotal = 0;
foreach ($_SESSION['cart'] as $product_id => $quantity) {
  $stmt = $conn->prepare("SELECT id, title, price, image, stock FROM products WHERE id = ?");
  $stmt->bind_param("i", $product_id);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($p = $res->fetch_assoc()) {
    $p['quantity'] = (int)$quantity;
    $p['total'] = $p['quantity'] * (float)$p['price'];
    $subtotal += $p['total'];
    $items[] = $p;
  }
  $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Thanh toán</title>
  <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
  <div class="checkout-wrapper">
    <h1 class="checkout-title">💰 THANH TOÁN</h1>

    <div class="checkout-grid">
      <section class="checkout-card">
        <h3 class="card-title">🧾 Đơn hàng của bạn:</h3>

        <?php foreach ($items as $it): ?>
          <p class="order-line">
            <?php echo htmlspecialchars($it['title']); ?> x <?php echo $it['quantity']; ?>
            = <strong><?php echo number_format($it['total'], 0, ',', '.'); ?> VND</strong>
          </p>
          <?php if (isset($it['stock'])): ?>
            <p class="stock-note">Số lượng hiện có: <?php echo (int)$it['stock']; ?></p>
          <?php endif; ?>
        <?php endforeach; ?>

        <div class="total-pill">
          <span>💸 Tổng cộng:</span>
          <strong><?php echo number_format($subtotal, 0, ',', '.'); ?> VND</strong>
        </div>
      </section>

      <aside class="checkout-card">
        <h3 class="card-title">🙍‍♂️ Thông tin khách hàng</h3>
        <form method="post" action="place_order.php" class="checkout-form">
          <label>Họ tên:</label>
          <input type="text" name="name" placeholder="Nhập họ tên" required>

          <label>Số điện thoại:</label>
          <input type="text" name="phone" placeholder="Nhập số điện thoại" required>

          <label>Địa chỉ:</label>
          <textarea name="address" rows="4" placeholder="Nhập địa chỉ nhận hàng" required></textarea>

          <div class="qr-box">
            <p class="qr-title">Thanh toán chuyển khoản qua QR</p>
            <p class="qr-desc">Quét mã để thanh toán. Vui lòng ghi rõ nội dung: <strong>Họ tên + SĐT</strong>.</p>
            <div class="qr-row">
              <div class="qr-img-wrap">
                <img src="../images/qr1.jpg" alt="QR chuyển khoản">
              </div>
              <div class="qr-info">
                <div><strong>Ngân hàng:</strong>Vietcombank</div>
                <div><strong>Số tài khoản:</strong> 1023148671 </div>
                <div><strong>Chủ tài khoản:</strong>VŨ TUẤN MINH</div>
                <div><strong>Nội dung:</strong> Họ tên + SĐT</div>
              </div>
            </div>
          </div>

          <button type="submit" class="btn-primary">✅ ĐẶT HÀNG NGAY</button>
        </form>
      </aside>
    </div>

    <div class="checkout-actions">
      <a href="index.php" class="btn-secondary">💬 Quay lại giỏ hàng</a>
    </div>
  </div>
</body>
</html>

