<!-- doanh thu -->
<?php
include "config.php";
include "session.php";
requireLogin();
requireAdmin();

// Helper to get a single numeric value
function fetchValue(mysqli $conn, string $sql, string $types = '', array $params = []) {
    $stmt = $conn->prepare($sql);
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $stmt->bind_result($val);
    $stmt->fetch();
    $stmt->close();
    return $val ?? 0;
}

// Revenue metrics (only tính đơn đã giao)
$revenueToday  = fetchValue($conn, "SELECT COALESCE(SUM(total),0) FROM orders WHERE status='delivered' AND DATE(created_at)=CURDATE()");
$revenueMonth  = fetchValue($conn, "SELECT COALESCE(SUM(total),0) FROM orders WHERE status='delivered' AND DATE_FORMAT(created_at,'%Y-%m') = DATE_FORMAT(CURDATE(),'%Y-%m')");
$revenueAll    = fetchValue($conn, "SELECT COALESCE(SUM(total),0) FROM orders WHERE status='delivered'");

// Order counts
$ordersAll     = fetchValue($conn, "SELECT COUNT(*) FROM orders");
$ordersPending = fetchValue($conn, "SELECT COUNT(*) FROM orders WHERE status='pending'");
$ordersShip    = fetchValue($conn, "SELECT COUNT(*) FROM orders WHERE status IN ('shipping','confirmed')");
$ordersDone    = fetchValue($conn, "SELECT COUNT(*) FROM orders WHERE status='delivered'");

// Top sản phẩm bán chạy (theo quantity đã giao)
$topProducts = [];
$topSql = "SELECT p.title, SUM(o.quantity) as qty, SUM(o.total) as amount
           FROM orders o
           JOIN products p ON o.product_id = p.id
           WHERE o.status = 'delivered'
           GROUP BY o.product_id
           ORDER BY qty DESC
           LIMIT 5";
$res = $conn->query($topSql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $topProducts[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Thống kê doanh thu</title>
  <link rel="stylesheet" href="../CSS/style.css">
  <link rel="stylesheet" href="../CSS/admin_dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
  <header>
    <div class="logo">TH BOOKs</div>
    <div class="controls">
      <a href="index.php" class="btn"><i class="fas fa-home"></i> Trang chủ</a>
      <a href="order_history.php" class="btn"><i class="fas fa-history"></i> Lịch sử đơn</a>
      <a href="logout.php" class="btn">Đăng xuất</a>
    </div>
  </header>
  <div class="subnav">
    <a href="admin_orders.php" class="btn btn-subnav"><i class="fas fa-clipboard-list"></i> Quản lý đơn hàng</a>
    <a href="index.php?open=addProduct" class="btn btn-subnav"><i class="fas fa-plus-circle"></i> Thêm sản phẩm</a>
    <a href="admin_dashboard.php" class="btn btn-subnav active"><i class="fas fa-chart-line"></i> Thống kê</a>
  </div>

  <div class="dashboard-wrap">
    <h1 style="margin-bottom:1.5rem; color: var(--primary); display:flex; align-items:center; gap:0.5rem;">
      <i class="fas fa-chart-line"></i> Thống kê doanh thu
    </h1>

    <div class="dash-grid">
      <div class="dash-card">
        <div class="dash-title"><i class="fas fa-wallet"></i> Doanh thu hôm nay</div>
        <div class="dash-value">$<?php echo number_format($revenueToday, 2, '.', ','); ?></div>
        <div class="dash-sub">Đơn đã giao trong ngày</div>
      </div>
      <div class="dash-card">
        <div class="dash-title"><i class="fas fa-calendar-alt"></i> Doanh thu tháng này</div>
        <div class="dash-value">$<?php echo number_format($revenueMonth, 2, '.', ','); ?></div>
        <div class="dash-sub">Đơn đã giao trong tháng</div>
      </div>
      <div class="dash-card">
        <div class="dash-title"><i class="fas fa-sack-dollar"></i> Doanh thu tích lũy</div>
        <div class="dash-value">$<?php echo number_format($revenueAll, 2, '.', ','); ?></div>
        <div class="dash-sub">Tất cả đơn đã giao</div>
      </div>
      <div class="dash-card">
        <div class="dash-title"><i class="fas fa-list"></i> Tổng đơn / Trạng thái</div>
        <div class="dash-value"><?php echo (int)$ordersAll; ?> đơn</div>
        <div class="dash-sub">
          Pending: <?php echo (int)$ordersPending; ?> |
          Đang xử lý/giao: <?php echo (int)$ordersShip; ?> |
          Đã giao: <?php echo (int)$ordersDone; ?>
        </div>
      </div>
    </div>

    <div class="dash-card" style="margin-top:1.5rem;">
      <div class="dash-title"><i class="fas fa-star"></i> Top 5 sản phẩm bán chạy (đã giao)</div>
      <?php if (empty($topProducts)): ?>
        <p style="color:#777;">Chưa có dữ liệu.</p>
      <?php else: ?>
        <table class="top-table">
          <thead>
            <tr>
              <th>Sản phẩm</th>
              <th>Số lượng</th>
              <th>Doanh thu</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($topProducts as $p): ?>
              <tr>
                <td><?php echo htmlspecialchars($p['title']); ?></td>
                <td><?php echo (int)$p['qty']; ?></td>
                <td>$<?php echo number_format($p['amount'], 2, '.', ','); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
<?php $conn->close(); ?>

