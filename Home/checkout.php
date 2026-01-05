<!-- CHỨC NĂNG THANH TOÁN TỪ GIỎ HÀNG QUA -->
<?php
include "config.php";
include "session.php";
requireLogin();

// Kiểm tra giỏ hàng có tồn tại và không rỗng không thì chuyển về trang chủ
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
  header('Location: index.php');
  exit;
}
// Chuẩn bị danh sách sản phẩm trong giỏ hàng để hiển thị
$items = [];
$subtotal = 0;
foreach ($_SESSION['cart'] as $product_id => $quantity) {
  // Truy vấn thông tin sản phẩm từ database 
  $stmt = $conn->prepare("SELECT id, title, price, image, stock FROM products WHERE id = ?");
  $stmt->bind_param("i", $product_id);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($p = $res->fetch_assoc()) {
    // Thêm số lượng và thành tiền cho từng sản phẩm
    $p['quantity'] = (int)$quantity;
    $p['total'] = $p['quantity'] * (float)$p['price'];
    $subtotal += $p['total']; // CỘNG DỒN TỔNG TIỀN
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
  <title>Giỏ hàng</title>
  <link rel="stylesheet" href="../CSS/style.css">
</head>
<body>
  <div class="checkout-wrapper">
    <h1 class="checkout-title">🛒 GIỎ HÀNG</h1>

    <div class="checkout-grid">
      <section class="checkout-card" id="cartCard">
        <h3 class="card-title">🛒 Giỏ hàng của bạn</h3>
        <div id="cartList">
          <?php foreach ($items as $it): ?>
            <div class="cart-row" 
                 data-id="<?php echo (int)$it['id']; ?>" 
                 data-price="<?php echo number_format((float)$it['price'], 2, '.', ''); ?>"
                 data-stock="<?php echo (int)($it['stock'] ?? 0); ?>">
              <input type="checkbox" class="select-item" checked aria-label="Chọn mua">
              <!-- Ảnh sản phẩm (nếu có) -->
              <?php if (!empty($it['image'])): ?>
                <img src="../uploads/<?php echo htmlspecialchars($it['image']); ?>" alt="<?php echo htmlspecialchars($it['title']); ?>" style="width:48px;height:48px;object-fit:cover;border-radius:6px;margin:0 10px;">
              <?php endif; ?>
              <!--Tên sản phẩm-->
              <div style="flex:1;min-width:160px;">
                <strong><?php echo htmlspecialchars($it['title']); ?></strong>
                <div class="stock-note">Số lượng hiện có: <?php echo (int)($it['stock'] ?? 0); ?></div>
              </div>
              <!-- ĐIỀU CHỈNH SỐ LƯỢNG  -->
              <div class="qty">
                <button type="button" class="qty-btn" data-delta="-1" aria-label="Giảm">−</button>
                <input type="number" class="qty-input" min="1" max="<?php echo (int)($it['stock'] ?? 0); ?>" value="<?php echo min((int)$it['quantity'], (int)($it['stock'] ?? 0)); ?>" />
                <button type="button" class="qty-btn" data-delta="1" aria-label="Tăng">+</button>
              </div>
              <div class="line-total">
                $<?php echo number_format($it['price'] * $it['quantity'], 2, '.', ','); ?>
              </div>
              <button type="button" class="remove-btn" aria-label="Xóa" style="margin-left:10px;">✖</button>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="total-pill">
          <span>💸 Tổng cộng (đã chọn):</span>
          <strong>$<span id="selectedTotal"><?php echo number_format($subtotal, 2, '.', ','); ?></span></strong>
        </div>
      </section>

      <aside class="checkout-card">
        <h3 class="card-title">💳 Thanh toán</h3>
        <form method="post" action="place_order.php" class="checkout-form" id="checkoutForm">
          <label>Họ tên:</label>
          <input type="text" name="name" placeholder="Nhập họ tên" required>

          <label>Số điện thoại:</label>
          <input type="text" name="phone" placeholder="Nhập số điện thoại" required>

          <label>Địa chỉ:</label>
          <textarea name="address" rows="4" placeholder="Nhập địa chỉ nhận hàng" required></textarea>

          <label>Phương thức thanh toán:</label>
          <div class="payment-methods">
            <label class="payment-option">
              <input type="radio" name="payment_method" value="bank_transfer" checked>
              <span>💳 Chuyển khoản</span>
            </label>
            <label class="payment-option">
              <input type="radio" name="payment_method" value="cod">
              <span>📦 Thanh toán sau khi nhận hàng (COD)</span>
            </label>
          </div>

          <div class="qr-box" id="qrBox">
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

          <div class="cod-info" id="codInfo" style="display: none;">
            <p class="cod-title">💵 Thanh toán khi nhận hàng</p>
            <p class="cod-desc">Bạn sẽ thanh toán bằng tiền mặt khi nhận được hàng. Vui lòng chuẩn bị đúng số tiền.</p>
          </div>

          <button type="submit" class="btn-primary">✅ ĐẶT HÀNG NGAY</button>
        </form>
      </aside>
    </div>

    <div class="checkout-actions">
      <a href="index.php" class="btn-secondary">💬 Quay lại trang chủ</a>
    </div>
  </div>
  <script src="../javascript/checkout.js"></script>
</body>
</html>

