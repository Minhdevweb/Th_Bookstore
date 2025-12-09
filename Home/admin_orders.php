<?php
include "config.php";
include "session.php";
requireLogin();
requireAdmin(); // Chỉ admin mới được truy cập

// Lấy tất cả đơn hàng
$sql = "SELECT o.*, p.title, p.image, u.email 
        FROM orders o 
        JOIN products p ON o.product_id = p.id 
        JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC";
$result = $conn->query($sql);
$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn hàng - Admin</title>
    <link rel="stylesheet" href="../CSS/style.css">
    <link rel="stylesheet" href="../CSS/order_history.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <div class="logo">TH BOOKs - Admin</div>
        <div class="controls">
            <a href="index.php" class="btn"><i class="fas fa-home"></i> Trang chủ</a>
            <a href="order_history.php" class="btn"><i class="fas fa-history"></i> Lịch sử đơn hàng</a>
            <a href="logout.php" class="btn">Đăng xuất</a>
        </div>
    </header>

    <div class="order-history-container">
        <h1 class="page-title">
            <i class="fas fa-cog"></i> Quản lý đơn hàng
        </h1>

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

