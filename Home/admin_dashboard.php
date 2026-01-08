<?php
/**
 * Trang thống kê doanh thu dành cho Admin
 * Hiển thị các chỉ số doanh thu, số lượng đơn hàng, top sản phẩm bán chạy và biểu đồ doanh thu
 */

// Kết nối database và kiểm tra session
include "config.php";
include "session.php";

// Kiểm tra quyền truy cập: yêu cầu đăng nhập và quyền admin
requireLogin(); // Bắt buộc phải đăng nhập
requireAdmin(); // Chỉ admin mới được truy cập trang này

/**
 * Helper function: Lấy một giá trị số duy nhất từ database
 * Sử dụng prepared statement để tránh SQL injection
 * 
 * @param mysqli $conn - Kết nối database
 * @param string $sql - Câu lệnh SQL cần thực thi
 * @param string $types - Chuỗi định nghĩa loại tham số ('s'=string, 'i'=integer, 'd'=double)
 * @param array $params - Mảng chứa các giá trị tham số để bind vào SQL
 * @return float|int - Giá trị trả về từ query, mặc định là 0 nếu không có dữ liệu
 */
function fetchValue(mysqli $conn, string $sql, string $types = '', array $params = []) {
    $stmt = $conn->prepare($sql); // Chuẩn bị câu lệnh SQL
    
    // Nếu có tham số, bind vào câu lệnh để tránh SQL injection
    if ($types) {
        $stmt->bind_param($types, ...$params); // Gắn tham số động
    }
    
    $stmt->execute(); // Thực thi câu lệnh
    $stmt->bind_result($val); // Gắn biến để nhận kết quả
    $stmt->fetch(); // Lấy kết quả từ query
    $stmt->close(); // Đóng statement
    
    return $val ?? 0; // Trả về giá trị hoặc 0 nếu không có dữ liệu
}

/* ===== THỐNG KÊ DOANH THU ===== */
// Lưu ý: Chỉ tính doanh thu từ các đơn hàng có trạng thái 'delivered' (đã giao)
// COALESCE() trả về giá trị không NULL đầu tiên, giúp tránh NULL khi tính tổng

// Doanh thu hôm nay: Tổng tiền các đơn đã giao trong ngày hiện tại
$revenueToday = fetchValue($conn, 
    "SELECT COALESCE(SUM(total),0) 
     FROM orders 
     WHERE status='delivered' 
     AND DATE(created_at)=CURDATE()"
);

// Doanh thu tháng này: Tổng tiền các đơn đã giao trong tháng hiện tại
// DATE_FORMAT() để so sánh theo định dạng năm-tháng (YYYY-MM)
$revenueMonth = fetchValue($conn, 
    "SELECT COALESCE(SUM(total),0) 
     FROM orders 
     WHERE status='delivered' 
     AND DATE_FORMAT(created_at,'%Y-%m') = DATE_FORMAT(CURDATE(),'%Y-%m')"
);

// Doanh thu tích lũy: Tổng tiền tất cả các đơn đã giao từ trước đến nay
$revenueAll = fetchValue($conn, 
    "SELECT COALESCE(SUM(total),0) 
     FROM orders 
     WHERE status='delivered'"
);

/* ===== THỐNG KÊ SỐ LƯỢNG ĐƠN HÀNG ===== */

// Tổng số đơn hàng (tất cả các trạng thái)
$ordersAll = fetchValue($conn, "SELECT COUNT(*) FROM orders");

// Số đơn hàng đang chờ xử lý (status = 'pending')
$ordersPending = fetchValue($conn, 
    "SELECT COUNT(*) 
     FROM orders 
     WHERE status='pending'"
);

// Số đơn hàng đang được xử lý/giao hàng (status = 'shipping' hoặc 'confirmed')
$ordersShip = fetchValue($conn, 
    "SELECT COUNT(*) 
     FROM orders 
     WHERE status IN ('shipping','confirmed')"
);

// Số đơn hàng đã hoàn thành (status = 'delivered')
$ordersDone = fetchValue($conn, 
    "SELECT COUNT(*) 
     FROM orders 
     WHERE status='delivered'"
);

/* ===== TOP 5 SẢN PHẨM BÁN CHẠY ===== */
// Lấy danh sách 5 sản phẩm có số lượng bán nhiều nhất (chỉ tính đơn đã giao)

$topProducts = []; // Mảng lưu trữ kết quả

// Query lấy top sản phẩm:
// - JOIN bảng orders và products để lấy thông tin sản phẩm
// - GROUP BY để nhóm theo product_id
// - SUM() để tính tổng số lượng (qty) và tổng doanh thu (amount)
// - ORDER BY qty DESC để sắp xếp theo số lượng giảm dần
// - LIMIT 5 để chỉ lấy 5 sản phẩm đầu tiên
$topSql = "SELECT p.title, SUM(o.quantity) as qty, SUM(o.total) as amount 
           FROM orders o
           JOIN products p ON o.product_id = p.id   
           WHERE o.status = 'delivered'
           GROUP BY o.product_id
           ORDER BY qty DESC
           LIMIT 5";

$res = $conn->query($topSql); // Thực thi query

// Lưu kết quả vào mảng $topProducts
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $topProducts[] = $row; // Thêm từng dòng kết quả vào mảng
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Favicon inline để tránh lỗi 404 -->
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' fill='%230b5ed7'/%3E%3Cpath d='M20 25h40v50H20z' fill='%23fff'/%3E%3C/svg%3E">
  <title>Thống kê doanh thu - Admin Dashboard</title>
  
  <!-- CSS Files -->
  <link rel="stylesheet" href="../CSS/style.css">
  <link rel="stylesheet" href="../CSS/admin_dashboard.css">
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
  <!-- Header Navigation -->
  <header>
    <div class="logo">TH BOOKs</div>
    <div class="controls">
      <a href="index.php" class="btn"><i class="fas fa-home"></i> Trang chủ</a>
      <a href="order_history.php" class="btn"><i class="fas fa-history"></i> Lịch sử đơn</a>
      <a href="logout.php" class="btn">Đăng xuất</a>
    </div>
  </header>
  
  <!-- Sub Navigation - Menu điều hướng cho admin -->
  <div class="subnav">
    <a href="admin_orders.php" class="btn btn-subnav">
      <i class="fas fa-clipboard-list"></i> Quản lý đơn hàng
    </a>
    <a href="index.php?open=addProduct" class="btn btn-subnav">
      <i class="fas fa-plus-circle"></i> Thêm sản phẩm
    </a>
    <a href="admin_dashboard.php" class="btn btn-subnav active">
      <i class="fas fa-chart-line"></i> Thống kê
    </a>
  </div>

  <!-- Main Dashboard Content -->
  <div class="dashboard-wrap">
    <h1>
      <i class="fas fa-chart-line"></i> Thống kê doanh thu
    </h1>

    <!-- Grid hiển thị các chỉ số doanh thu và đơn hàng -->
    <div class="dash-grid">
      <!-- Card: Doanh thu hôm nay -->
      <div class="dash-card">
        <div class="dash-title">
          <i class="fas fa-wallet"></i> Doanh thu hôm nay
        </div>
        <!-- number_format(): Format số với 2 chữ số thập phân, dấu phẩy ngăn cách hàng nghìn -->
        <div class="dash-value">$<?php echo number_format($revenueToday, 2, '.', ','); ?></div>  
        <div class="dash-sub">Đơn đã giao trong ngày</div>
      </div>
      
      <!-- Card: Doanh thu tháng này -->
      <div class="dash-card">
        <div class="dash-title">
          <i class="fas fa-calendar-alt"></i> Doanh thu tháng này
        </div>
        <div class="dash-value">$<?php echo number_format($revenueMonth, 2, '.', ','); ?></div>
        <div class="dash-sub">Đơn đã giao trong tháng</div>
      </div>
      
      <!-- Card: Doanh thu tích lũy -->
      <div class="dash-card">
        <div class="dash-title">
          <i class="fas fa-sack-dollar"></i> Doanh thu tích lũy
        </div>
        <div class="dash-value">$<?php echo number_format($revenueAll, 2, '.', ','); ?></div>
        <div class="dash-sub">Tất cả đơn đã giao</div>
      </div>
      
      <!-- Card: Tổng số đơn hàng và trạng thái -->
      <div class="dash-card">
        <div class="dash-title">
          <i class="fas fa-list"></i> Tổng đơn / Trạng thái
        </div>
        <!-- (int) để ép kiểu về integer, đảm bảo hiển thị số nguyên -->
        <div class="dash-value"><?php echo (int)$ordersAll; ?> đơn</div>
        <div class="dash-sub">
          Pending: <?php echo (int)$ordersPending; ?> |
          Đang xử lý/giao: <?php echo (int)$ordersShip; ?> |
          Đã giao: <?php echo (int)$ordersDone; ?>
        </div>
      </div>
    </div>

    <!-- Biểu đồ doanh thu -->
    <div class="dash-card" style="margin-top:1.5rem;">
      <div class="dash-title">
        <i class="fas fa-chart-line"></i> Biểu đồ doanh thu
      </div>
      
      <!-- Nút chuyển đổi loại biểu đồ -->
      <div class="chart-controls">
        <button class="chart-btn active" data-type="daily">
          <i class="fas fa-calendar-day"></i> Theo ngày (30 ngày)
        </button>
        <button class="chart-btn" data-type="weekly">
          <i class="fas fa-calendar-week"></i> Theo tuần (12 tuần)
        </button>
        <button class="chart-btn" data-type="monthly">
          <i class="fas fa-calendar-alt"></i> Theo tháng (12 tháng)
        </button>
      </div>
      
      <!-- Container cho biểu đồ -->
      <div class="chart-container">
        <canvas id="revenueChart"></canvas>
      </div>
    </div>

    <!-- Bảng hiển thị Top 5 sản phẩm bán chạy nhất -->
    <div class="dash-card" style="margin-top:1.5rem;">
      <div class="dash-title">
        <i class="fas fa-star"></i> Top 5 sản phẩm bán chạy (đã giao)
      </div>
      
      <!-- Kiểm tra xem có dữ liệu sản phẩm không -->
      <?php if (empty($topProducts)): ?>
        <!-- Hiển thị thông báo nếu chưa có dữ liệu -->
        <p style="color:#777;">Chưa có dữ liệu.</p>
      <?php else: ?>
        <!-- Bảng hiển thị danh sách sản phẩm -->
        <table class="top-table">
          <thead>
            <tr>
              <th>Sản phẩm</th>
              <th>Số lượng</th>
              <th>Doanh thu</th>
            </tr>
          </thead>
          <tbody>
            <!-- Duyệt qua từng sản phẩm trong mảng $topProducts -->
            <?php foreach ($topProducts as $p): ?>
              <tr>
                <!-- htmlspecialchars() để escape HTML, tránh XSS attack -->
                <td><?php echo htmlspecialchars($p['title']); ?></td>
                <!-- Ép kiểu về integer để hiển thị số nguyên -->
                <td><?php echo (int)$p['qty']; ?></td>
                <!-- Format số tiền với 2 chữ số thập phân -->
                <td>$<?php echo number_format($p['amount'], 2, '.', ','); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>

  <!-- Thêm thư viện Chart.js từ CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  
  <!-- Script để vẽ biểu đồ -->
  <script>
    /**
     * Khởi tạo và quản lý biểu đồ doanh thu
     * Sử dụng Chart.js để hiển thị dữ liệu doanh thu theo nhiều khoảng thời gian
     */
    
    let revenueChart = null; // Biến lưu trữ instance của biểu đồ
    let currentType = 'daily'; // Loại biểu đồ hiện tại (daily, weekly, monthly)
    
    /**
     * Hàm tạo biểu đồ doanh thu
     * @param {Array} labels - Mảng nhãn thời gian (ngày/tuần/tháng)
     * @param {Array} data - Mảng dữ liệu doanh thu tương ứng
     * @param {string} type - Loại biểu đồ ('daily', 'weekly', 'monthly')
     */
    function createChart(labels, data, type) {
      const ctx = document.getElementById('revenueChart').getContext('2d');
      
      // Xóa biểu đồ cũ nếu đã tồn tại
      if (revenueChart) {
        revenueChart.destroy();
      }
      
      // Tạo biểu đồ đường (line chart) mới
      revenueChart = new Chart(ctx, {
        type: 'line', // Loại biểu đồ: đường
        data: {
          labels: labels, // Nhãn trục X (thời gian)
          datasets: [{
            label: 'Doanh thu ($)', // Tên của dataset
            data: data, // Dữ liệu doanh thu
            borderColor: 'rgb(75, 192, 192)', // Màu đường biểu đồ
            backgroundColor: 'rgba(75, 192, 192, 0.1)', // Màu nền vùng dưới đường
            borderWidth: 2, // Độ dày đường
            fill: true, // Tô màu vùng dưới đường
            tension: 0.4, // Độ cong của đường (0.4 = mượt mà)
            pointRadius: 4, // Kích thước điểm trên đường
            pointHoverRadius: 6, // Kích thước điểm khi hover
            pointBackgroundColor: 'rgb(75, 192, 192)', // Màu điểm
            pointBorderColor: '#fff', // Màu viền điểm
            pointBorderWidth: 2 // Độ dày viền điểm
          }]
        },
        options: {
          responsive: true, // Tự động điều chỉnh kích thước
          maintainAspectRatio: true, // Giữ tỷ lệ khung hình
          aspectRatio: 2.5, // Tỷ lệ chiều rộng/chiều cao
          plugins: {
            legend: {
              display: true, // Hiển thị chú thích
              position: 'top' // Vị trí chú thích
            },
            tooltip: {
              mode: 'index', // Hiển thị tooltip khi hover
              intersect: false, // Không yêu cầu hover chính xác vào điểm
              callbacks: {
                // Format giá trị trong tooltip
                label: function(context) {
                  return 'Doanh thu: $' + context.parsed.y.toFixed(2);
                }
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true, // Bắt đầu trục Y từ 0
              ticks: {
                // Format giá trị trên trục Y
                callback: function(value) {
                  return '$' + value.toFixed(2);
                }
              },
              title: {
                display: true,
                text: 'Doanh thu ($)' // Tiêu đề trục Y
              }
            },
            x: {
              title: {
                display: true,
                text: getXAxisTitle(type) // Tiêu đề trục X (thay đổi theo loại biểu đồ)
              }
            }
          }
        }
      });
    }
    
    /**
     * Lấy tiêu đề trục X dựa trên loại biểu đồ
     * @param {string} type - Loại biểu đồ
     * @return {string} - Tiêu đề trục X
     */
    function getXAxisTitle(type) {
      switch(type) {
        case 'daily': return 'Ngày';
        case 'weekly': return 'Tuần';
        case 'monthly': return 'Tháng';
        default: return 'Thời gian';
      }
    }
    
    /**
     * Hàm tải dữ liệu doanh thu từ API
     * @param {string} type - Loại biểu đồ cần tải ('daily', 'weekly', 'monthly')
     */
    function loadChartData(type) {
      // Hiển thị loading indicator (có thể thêm spinner nếu cần)
      const chartContainer = document.querySelector('.chart-container');
      
      // Gọi API để lấy dữ liệu
      fetch(`get_revenue_chart_data.php?type=${type}`)
        .then(response => {
          if (!response.ok) {
            throw new Error('Lỗi khi tải dữ liệu');
          }
          return response.json();
        })
        .then(result => {
          if (result.success) {
            // Tạo biểu đồ với dữ liệu nhận được
            createChart(result.labels, result.data, type);
          } else {
            console.error('Lỗi:', result.message);
            alert('Không thể tải dữ liệu biểu đồ: ' + result.message);
          }
        })
        .catch(error => {
          console.error('Lỗi:', error);
          alert('Có lỗi xảy ra khi tải dữ liệu biểu đồ');
        });
    }
    
    /**
     * Xử lý sự kiện click vào nút chuyển đổi loại biểu đồ
     */
    document.querySelectorAll('.chart-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const type = this.getAttribute('data-type');
        
        // Cập nhật trạng thái active của các nút
        document.querySelectorAll('.chart-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        // Tải dữ liệu và vẽ biểu đồ mới
        currentType = type;
        loadChartData(type);
      });
    });
    
    // Tải biểu đồ mặc định khi trang được tải
    document.addEventListener('DOMContentLoaded', function() {
      loadChartData('daily');
    });
  </script>
</body>
</html>
<?php $conn->close(); ?>

