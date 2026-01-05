<?php
// Lưu giỏ hàng vào localStorage trước khi hủy session để giữ sản phẩm sau khi đăng nhập lại
session_start();

// Nếu đã thực hiện xong việc lưu (bước 2), thì hủy session và chuyển hướng
if (isset($_GET['done']) && $_GET['done'] === '1') {
    // Xóa tất cả các biến session
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header("Location: index.php?login=1");
    exit();
}

// Bước 1: xuất HTML+JS để ghi giỏ hàng hiện tại vào localStorage, sau đó quay lại file này với ?done=1
$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$json = json_encode($cart);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Đăng xuất</title>
</head>
<body>
  <script>
    try {
      var existing = localStorage.getItem('guest_cart');
      var guest = existing ? JSON.parse(existing) : {};
      var serverCart = <?php echo $json; ?> || {};
      for (var id in serverCart) {
        if (!serverCart.hasOwnProperty(id)) continue;
        var qty = parseInt(serverCart[id], 10) || 1;
        guest[id] = (parseInt(guest[id] || '0', 10) || 0) + qty;
      }
      localStorage.setItem('guest_cart', JSON.stringify(guest));
    } catch (e) {}
    window.location.href = 'logout.php?done=1';
  </script>
</body>
</html>
