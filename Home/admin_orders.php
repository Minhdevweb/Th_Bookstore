<?php
include "config.php";
include "session.php";
requireLogin();
requireAdmin(); // Chỉ admin mới được truy cập

// Helper to get single value
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

// Bộ lọc khoảng ngày (YYYY-MM-DD)
$startDate = $_GET['start_date'] ?? '';
$endDate   = $_GET['end_date'] ?? '';

// Doanh thu mặc định (đơn đã giao)
$revenueToday = fetchValue($conn, "SELECT COALESCE(SUM(total),0) FROM orders WHERE status='delivered' AND DATE(created_at)=CURDATE()");
$revenueMonth = fetchValue($conn, "SELECT COALESCE(SUM(total),0) FROM orders WHERE status='delivered' AND DATE_FORMAT(created_at,'%Y-%m') = DATE_FORMAT(CURDATE(),'%Y-%m')");
$revenueAll   = fetchValue($conn, "SELECT COALESCE(SUM(total),0) FROM orders WHERE status='delivered'");

// Doanh thu theo khoảng ngày (đơn đã giao)
$types = '';
$params = [];
$rangeSql = "SELECT COALESCE(SUM(total),0) FROM orders WHERE status='delivered'";
if ($startDate) { $rangeSql .= " AND DATE(created_at) >= ?"; $types .= 's'; $params[] = $startDate; }
if ($endDate)   { $rangeSql .= " AND DATE(created_at) <= ?"; $types .= 's'; $params[] = $endDate; }
$revenueRange = fetchValue($conn, $rangeSql, $types, $params);

// Lấy tất cả đơn hàng (có thể lọc theo khoảng ngày)
$sql = "SELECT o.*, p.title, p.image, u.email 
        FROM orders o 
        JOIN products p ON o.product_id = p.id 
        JOIN users u ON o.user_id = u.id 
        WHERE 1=1";
$typesOrders = '';
$paramsOrders = [];
if ($startDate) { $sql .= " AND DATE(o.created_at) >= ?"; $typesOrders .= 's'; $paramsOrders[] = $startDate; }
if ($endDate)   { $sql .= " AND DATE(o.created_at) <= ?"; $typesOrders .= 's'; $paramsOrders[] = $endDate; }
$sql .= " ORDER BY o.created_at DESC";

$stmtOrders = $conn->prepare($sql);
if ($typesOrders) {
    $stmtOrders->bind_param($typesOrders, ...$paramsOrders);
}
$stmtOrders->execute();
$result = $stmtOrders->get_result();
$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
$stmtOrders->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn hàng</title>
    <link rel="stylesheet" href="../CSS/style.css">
    <link rel="stylesheet" href="../CSS/order_history.css">
    <link rel="stylesheet" href="../CSS/admin_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <div class="logo">TH BOOKs</div>
        <div class="controls">
            <a href="index.php" class="btn"><i class="fas fa-home"></i> Trang chủ</a>
            <a href="order_history.php" class="btn"><i class="fas fa-history"></i> Lịch sử đơn hàng</a>
            <a href="logout.php" class="btn">Đăng xuất</a>
        </div>
    </header>
    <div class="subnav">
        <a href="admin_orders.php" class="btn btn-subnav active"><i class="fas fa-clipboard-list"></i> Quản lý đơn hàng</a>
        <a href="admin_dashboard.php" class="btn btn-subnav"><i class="fas fa-chart-line"></i> Thống kê</a>
    </div>

    <div class="order-history-container">
        <h1 class="page-title">
            <i class="fas fa-cog"></i> Quản lý đơn hàng
        </h1>

        <!-- Thống kê nhanh + lọc khoảng ngày -->
        <div class="dash-card" style="margin-bottom:1rem;">
          <form method="get" style="display:flex; gap:0.75rem; align-items:center; flex-wrap:wrap;">
            <strong><i class="fas fa-filter"></i> Lọc doanh thu:</strong>
            <label>Từ ngày:</label>
            <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>" />
            <label>Đến ngày:</label>
            <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>" />
            <button type="submit" class="btn" style="background:var(--primary); color:#fff;"><i class="fas fa-search"></i> Áp dụng</button>
            <a href="admin_orders.php" class="btn" style="background:#e8f0ff; color:#1b5ad6; border:1px solid #c9dcff;">Xóa lọc</a>
          </form>
        </div>

        <div class="dash-grid" style="margin-bottom:1rem;">
          <div class="dash-card">
            <div class="dash-title"><i class="fas fa-wallet"></i> Doanh thu hôm nay</div>
            <div class="dash-value"><?php echo number_format($revenueToday, 0, ',', '.'); ?> VND</div>
            <div class="dash-sub">Đơn đã giao trong ngày</div>
          </div>
          <div class="dash-card">
            <div class="dash-title"><i class="fas fa-calendar-alt"></i> Doanh thu tháng này</div>
            <div class="dash-value"><?php echo number_format($revenueMonth, 0, ',', '.'); ?> VND</div>
            <div class="dash-sub">Đơn đã giao trong tháng</div>
          </div>
          <div class="dash-card">
            <div class="dash-title"><i class="fas fa-sack-dollar"></i> Doanh thu tích lũy</div>
            <div class="dash-value"><?php echo number_format($revenueAll, 0, ',', '.'); ?> VND</div>
            <div class="dash-sub">Tất cả đơn đã giao</div>
          </div>
          <div class="dash-card">
            <div class="dash-title"><i class="fas fa-sliders-h"></i> Doanh thu theo khoảng</div>
            <div class="dash-value"><?php echo number_format($revenueRange, 0, ',', '.'); ?> VND</div>
            <div class="dash-sub">
              <?php if ($startDate || $endDate): ?>
                <?php echo $startDate ? htmlspecialchars($startDate) : '...'; ?> → <?php echo $endDate ? htmlspecialchars($endDate) : '...'; ?>
              <?php else: ?>
                Chưa chọn ngày
              <?php endif; ?>
            </div>
          </div>
        </div>

        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>Chưa có đơn hàng nào</p>
            </div>
        <?php else: ?>
            <div class="orders-list">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card admin-card">
                        <div class="order-header">
                            <div class="order-info">
                                <h3>
                                    <i class="fas fa-book"></i> 
                                    <?php echo htmlspecialchars($order['title']); ?>
                                </h3>
                                <p class="customer-email">
                                    <i class="fas fa-user"></i> 
                                    Khách hàng: <?php echo htmlspecialchars($order['email']); ?>
                                </p>
                                <p class="order-date">
                                    <i class="fas fa-calendar"></i> 
                                    <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                                </p>
                                <p class="order-id">
                                    <i class="fas fa-hashtag"></i> 
                                    Mã đơn: #<?php echo $order['id']; ?>
                                </p>
                            </div>
                            <div class="order-status status-<?php echo $order['status']; ?>">
                                <?php
                                $status_text = [
                                    'pending' => 'Chờ xử lý',
                                    'confirmed' => 'Đã xác nhận',
                                    'shipping' => 'Đang giao hàng',
                                    'delivered' => 'Đã giao hàng',
                                    'cancelled' => 'Đã hủy'
                                ];
                                echo $status_text[$order['status']] ?? $order['status'];
                                ?>
                            </div>
                        </div>

                        <div class="order-details">
                            <?php if ($order['image']): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($order['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($order['title']); ?>" 
                                     class="product-image">
                            <?php endif; ?>
                            
                            <div class="order-info-details">
                                <div class="detail-row">
                                    <span class="label">Số lượng:</span>
                                    <span class="value"><?php echo $order['quantity']; ?> cuốn</span>
                                </div>
                                <div class="detail-row">
                                    <span class="label">Giá:</span>
                                    <span class="value"><?php echo number_format($order['total'] / $order['quantity'], 0, ',', '.'); ?> VND/cuốn</span>
                                </div>
                                <div class="detail-row">
                                    <span class="label">Tổng tiền:</span>
                                    <span class="value total-price"><?php echo number_format($order['total'], 0, ',', '.'); ?> VND</span>
                                </div>
                                
                                <?php if (!empty($order['customer_name'])): ?>
                                    <div class="detail-row">
                                        <span class="label">Người nhận:</span>
                                        <span class="value"><?php echo htmlspecialchars($order['customer_name']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($order['customer_phone'])): ?>
                                    <div class="detail-row">
                                        <span class="label">Số điện thoại:</span>
                                        <span class="value"><?php echo htmlspecialchars($order['customer_phone']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($order['customer_address'])): ?>
                                    <div class="detail-row">
                                        <span class="label">Địa chỉ:</span>
                                        <span class="value"><?php echo htmlspecialchars($order['customer_address']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Phần cập nhật trạng thái cho admin -->
                        <div class="admin-actions">
                            <label>Cập nhật trạng thái:</label>
                            <select class="status-select" data-order-id="<?php echo $order['id']; ?>">
                                <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Chờ xử lý</option>
                                <option value="confirmed" <?php echo $order['status'] == 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
                                <option value="shipping" <?php echo $order['status'] == 'shipping' ? 'selected' : ''; ?>>Đang giao hàng</option>
                                <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Đã giao hàng</option>
                                <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                            </select>
                            <button class="btn-update-status" data-order-id="<?php echo $order['id']; ?>">
                                <i class="fas fa-save"></i> Cập nhật
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <footer style="text-align: center; padding: 2rem; margin-top: 3rem; border-top: 1px solid var(--border);">
        <p>&copy; 2025 TH Bookstore. All rights reserved.</p>
    </footer>

    <script src="../javascript/admin_orders.js"></script>
</body>
</html>
<?php $conn->close(); ?>

