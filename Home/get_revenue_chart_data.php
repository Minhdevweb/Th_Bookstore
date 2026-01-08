<?php
/**
 * API endpoint để lấy dữ liệu doanh thu cho biểu đồ
 * Trả về dữ liệu JSON với các loại biểu đồ: theo ngày, theo tuần, theo tháng
 */

// Bắt đầu session và kiểm tra quyền truy cập
include "config.php";
include "session.php";
requireLogin(); // Yêu cầu đăng nhập
requireAdmin(); // Chỉ admin mới được truy cập

// Thiết lập header JSON
header('Content-Type: application/json');

/**
 * Helper function để lấy giá trị số từ database
 * @param mysqli $conn - Kết nối database
 * @param string $sql - Câu lệnh SQL
 * @param string $types - Loại tham số (s=string, i=integer, d=double)
 * @param array $params - Mảng các tham số
 * @return float - Giá trị trả về, mặc định là 0 nếu không có dữ liệu
 */
function fetchValue(mysqli $conn, string $sql, string $types = '', array $params = []) {
    $stmt = $conn->prepare($sql);
    if ($types) {
        $stmt->bind_param($types, ...$params); // Gắn tham số động để tránh SQL injection
    }
    $stmt->execute();
    $stmt->bind_result($val);
    $stmt->fetch();
    $stmt->close();
    return $val ?? 0; // Trả về 0 nếu không có dữ liệu
}

/**
 * Lấy doanh thu theo ngày trong 30 ngày gần nhất
 * @return array - Mảng chứa labels (ngày) và data (doanh thu)
 */
function getDailyRevenue($conn) {
    $data = [];
    $labels = [];
    
    // Lấy doanh thu cho 30 ngày gần nhất
    for ($i = 29; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $dateFormatted = date('d/m', strtotime("-$i days")); // Format hiển thị: dd/mm
        
        // Tính tổng doanh thu trong ngày (chỉ đơn đã giao)
        $sql = "SELECT COALESCE(SUM(total), 0) 
                FROM orders 
                WHERE status = 'delivered' 
                AND DATE(created_at) = ?";
        $revenue = fetchValue($conn, $sql, 's', [$date]);
        
        $labels[] = $dateFormatted;
        $data[] = (float)$revenue;
    }
    
    return ['labels' => $labels, 'data' => $data];
}

/**
 * Lấy doanh thu theo tuần trong 12 tuần gần nhất
 * @return array - Mảng chứa labels (tuần) và data (doanh thu)
 */
function getWeeklyRevenue($conn) {
    $data = [];
    $labels = [];
    
    // Lấy doanh thu cho 12 tuần gần nhất
    for ($i = 11; $i >= 0; $i--) {
        $weekStart = date('Y-m-d', strtotime("-$i weeks monday"));
        $weekEnd = date('Y-m-d', strtotime("$weekStart +6 days"));
        $weekLabel = date('d/m', strtotime($weekStart)) . ' - ' . date('d/m', strtotime($weekEnd));
        
        // Tính tổng doanh thu trong tuần (chỉ đơn đã giao)
        $sql = "SELECT COALESCE(SUM(total), 0) 
                FROM orders 
                WHERE status = 'delivered' 
                AND DATE(created_at) >= ? 
                AND DATE(created_at) <= ?";
        $revenue = fetchValue($conn, $sql, 'ss', [$weekStart, $weekEnd]);
        
        $labels[] = $weekLabel;
        $data[] = (float)$revenue;
    }
    
    return ['labels' => $labels, 'data' => $data];
}

/**
 * Lấy doanh thu theo tháng trong 12 tháng gần nhất
 * @return array - Mảng chứa labels (tháng) và data (doanh thu)
 */
function getMonthlyRevenue($conn) {
    $data = [];
    $labels = [];
    
    // Lấy doanh thu cho 12 tháng gần nhất
    for ($i = 11; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $monthLabel = date('m/Y', strtotime("$month-01")); // Format hiển thị: mm/yyyy
        
        // Tính tổng doanh thu trong tháng (chỉ đơn đã giao)
        $sql = "SELECT COALESCE(SUM(total), 0) 
                FROM orders 
                WHERE status = 'delivered' 
                AND DATE_FORMAT(created_at, '%Y-%m') = ?";
        $revenue = fetchValue($conn, $sql, 's', [$month]);
        
        $labels[] = $monthLabel;
        $data[] = (float)$revenue;
    }
    
    return ['labels' => $labels, 'data' => $data];
}

// Xử lý request và trả về dữ liệu
try {
    $type = $_GET['type'] ?? 'daily'; // Mặc định là biểu đồ theo ngày
    
    switch ($type) {
        case 'daily':
            $result = getDailyRevenue($conn);
            break;
        case 'weekly':
            $result = getWeeklyRevenue($conn);
            break;
        case 'monthly':
            $result = getMonthlyRevenue($conn);
            break;
        default:
            $result = getDailyRevenue($conn);
    }
    
    // Trả về dữ liệu dưới dạng JSON
    echo json_encode([
        'success' => true,
        'type' => $type,
        'labels' => $result['labels'],
        'data' => $result['data']
    ]);
    
} catch (Exception $e) {
    // Xử lý lỗi và trả về thông báo
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi lấy dữ liệu: ' . $e->getMessage()
    ]);
}

$conn->close();
?>

