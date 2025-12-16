<?php
include "config.php";
include "session.php";
requireLogin();

// Lấy user_id từ session
$user_id = $_SESSION['user_id'];
$is_admin = isAdmin();

// Lấy danh sách đơn hàng.
if ($is_admin) {
    // Admin: Lấy TẤT CẢ đơn hàng
    $sql = "SELECT o.*, p.title, p.image, u.email 
            FROM orders o 
            JOIN products p ON o.product_id = p.id 
            JOIN users u ON o.user_id = u.id 
            ORDER BY o.created_at DESC";
    $stmt = $conn->prepare($sql);
} else {
    // Khách hàng: Chỉ lấy đơn hàng CỦA MÌNH
    $sql = "SELECT o.*, p.title, p.image 
            FROM orders o 
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
$stmt->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử mua hàng</title>
    <link rel="stylesheet" href="../CSS/style.css">
    <link rel="stylesheet" href="../CSS/order_history.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <div class="logo">TH BOOKs</div>
        <div class="controls">
            <a href="index.php" class="btn"><i class="fas fa-home"></i> Trang chủ</a>
            <?php if ($is_admin): ?>
                <a href="admin_orders.php" class="btn"><i class="fas fa-cog"></i> Quản lý đơn hàng</a>
            <?php endif; ?>
            <a href="logout.php" class="btn">Đăng xuất</a>
        </div>
    </header>

    <div class="order-history-container">
        <h1 class="page-title">
            <i class="fas fa-history"></i> 
            <?php echo $is_admin ? 'Tất cả đơn hàng' : 'Lịch sử mua hàng của bạn'; ?>
        </h1>

        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <i class="fas fa-shopping-bag"></i>
                <p>Bạn chưa có đơn hàng nào</p>
                <a href="index.php" class="btn-primary">Mua sắm ngay</a>
            </div>
        <?php else: ?>
            <div class="orders-list">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-info">
                                <h3>
                                    <i class="fas fa-book"></i> 
                                    <?php echo htmlspecialchars($order['title']); ?>
                                </h3>
                                <?php if ($is_admin): ?>
                                    <p class="customer-email">
                                        <i class="fas fa-user"></i> 
                                        <?php echo htmlspecialchars($order['email']); ?>
                                    </p>
                                <?php endif; ?>
                                <p class="order-date">
                                    <i class="fas fa-calendar"></i> 
                                    <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
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
                                    <span class="value">$<?php echo number_format($order['total'] / $order['quantity'], 2, '.', ','); ?>/cuốn</span>
                                </div>
                                <div class="detail-row">
                                    <span class="label">Tổng tiền:</span>
                                    <span class="value total-price">$<?php echo number_format($order['total'], 2, '.', ','); ?></span>
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
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <footer style="text-align: center; padding: 2rem; margin-top: 3rem; border-top: 1px solid var(--border);">
        <p>&copy; 2025 TH Bookstore. All rights reserved.</p>
    </footer>
</body>
</html>
<?php $conn->close(); ?>