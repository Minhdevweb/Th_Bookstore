<?php
// Trả về JSON cho mọi request của chatbot
header('Content-Type: application/json');
// Kết nối database (sử dụng cùng config với trang chính)
include "config.php";

// ----- 1. Lấy và kiểm tra input -----
$userMessage = isset($_POST['message']) ? trim($_POST['message']) : '';

if ($userMessage === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Vui lòng nhập nội dung cần tư vấn.'
    ]);
    exit;
}

// Chuyển sang lowercase (có hỗ trợ tiếng Việt) để dễ kiểm tra từ khóa
$message = mb_strtolower($userMessage, 'UTF-8');

// Tách riêng phần "tác giả ..." để lọc theo cột author chính xác hơn
$authorTokens = [];
if (preg_match('/(?:tác giả|tac gia|author)\s+([a-zA-ZÀ-ỹđ\s]+)/u', $message, $match)) {
    $authorPhrase = trim($match[1]);
    $authorTokens = array_filter(
        preg_split('/\s+/u', $authorPhrase),
        function ($token) {
            return $token !== '';
        }
    );
    // Bỏ đoạn "tác giả ..." khỏi câu hỏi để phần còn lại tìm theo title
    $message = trim(str_replace($match[0], '', $message));
}

// Loại bỏ các hư từ phổ biến để không bắt buộc trong tiêu đề
$message = str_replace(['của', 'cua'], ' ', $message);

// Đảm bảo bảng có cột is_active (nếu chưa có)
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1");

// ----- 2. Chuẩn bị truy vấn sản phẩm -----
$sql = "SELECT * FROM products WHERE is_active = 1";
$conditions = [];

// 2.1. Tách từ khóa để tìm trong title/author
$searchTerms = preg_split('/\s+/u', $message);
$titleConditions = [];
$authorConditions = [];

// Những từ nên loại bỏ vì không mang ý nghĩa tìm kiếm
$stopWords = [
    'tac', 'tác', 'gia', 'giả', 'author',
    'toi', 'tôi', 'ban', 'bạn', 'giup', 'giúp',
    'xin', 'hay', 'hãy', 'cam', 'cần', 'muon', 'muốn',
    'voi', 'với', 'lam', 'làm', 'gi', 'gì'
];

foreach ($searchTerms as $term) {
    $term = trim($term);
    if ($term === '') {
        continue;
    }

    if (mb_strlen($term, 'UTF-8') <= 2) {
        continue;
    }

    if (in_array($term, $stopWords, true)) {
        continue;
    }

    $safe = $conn->real_escape_string($term);
    $titleConditions[] = "title LIKE '%{$safe}%'";
    $authorConditions[] = "author LIKE '%{$safe}%'";
}

// Nếu người dùng chỉ định rõ tên tác giả thì ưu tiên thêm vào điều kiện author
foreach ($authorTokens as $token) {
    if (mb_strlen($token, 'UTF-8') <= 1) {
        continue;
    }
    $safe = $conn->real_escape_string($token);
    $authorConditions[] = "author LIKE '%{$safe}%'";
}

if (!empty($titleConditions) || !empty($authorConditions)) {
    // Đảm bảo tiêu đề phải chứa toàn bộ từ khóa hoặc tác giả phải chứa toàn bộ
    $clauses = [];

    if (!empty($titleConditions)) {
        $clauses[] = '(' . implode(' AND ', $titleConditions) . ')';
    }

    if (!empty($authorConditions)) {
        $clauses[] = '(' . implode(' AND ', $authorConditions) . ')';
    }

    $conditions[] = '(' . implode(' OR ', $clauses) . ')';
}

// 2.2. Nhận diện category từ các từ khóa tiếng Việt/Anh
$categoryKeywords = [
    'english books' => ['english books', 'sách tiếng anh', 'sach tieng anh'],
    'vietnamese books' => ['vietnamese books', 'sách tiếng việt', 'sach tieng viet'],
    'stationery' => ['stationery', 'văn phòng phẩm', 'van phong pham']
];

foreach ($categoryKeywords as $category => $keywords) {
    foreach ($keywords as $keyword) {
        if (strpos($message, $keyword) !== false) {
            $safeCat = $conn->real_escape_string($category);
            $conditions[] = "LOWER(category) = '{$safeCat}'";
            break 2; // Thoát cả hai vòng lặp khi đã tìm được
        }
    }
}

// 2.3. Áp dụng bộ lọc giá nếu user có đề cập số tiền
if (preg_match('/(\d+(\.\d+)?)/', $message, $matches)) {
    $price = (float) $matches[1];
    if (
        strpos($message, 'dưới') !== false ||
        strpos($message, 'duoi') !== false ||
        strpos($message, 'below') !== false ||
        strpos($message, 'under') !== false ||
        strpos($message, 'nhỏ hơn') !== false ||
        strpos($message, '<') !== false
    ) {
        $conditions[] = "price <= {$price}";
    } elseif (
        strpos($message, 'trên') !== false ||
        strpos($message, 'tren') !== false ||
        strpos($message, 'over') !== false ||
        strpos($message, 'above') !== false ||
        strpos($message, 'lớn hơn') !== false ||
        strpos($message, '>') !== false
    ) {
        $conditions[] = "price >= {$price}";
    } else {
        // Nếu chỉ nêu giá cụ thể, giới hạn ±20%
        $delta = $price * 0.2;
        $min = max(0, $price - $delta);
        $max = $price + $delta;
        $conditions[] = "(price BETWEEN {$min} AND {$max})";
    }
}

// Gắn các điều kiện vào query
if (!empty($conditions)) {
    $sql .= ' AND ' . implode(' AND ', $conditions);
}

// Ưu tiên sách rating cao và giới hạn số lượng trả về
$sql .= ' ORDER BY rating DESC LIMIT 5';

// ----- 3. Thực thi query và chuẩn bị dữ liệu -----
$result = $conn->query($sql);
$products = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Chuẩn hóa đường dẫn ảnh để frontend hiển thị đúng
        $img = trim($row['image']);
        if ($img !== '' && !str_starts_with($img, 'http') && !str_starts_with($img, '../uploads/')) {
            $img = '../uploads/' . basename($img);
        }
        $row['image'] = $img;
        $products[] = $row;
    }
}

// ----- 4. Tạo thông điệp phản hồi -----
if (empty($products)) {
    $responseMessage = 'Xin lỗi, mình chưa tìm được cuốn sách nào đúng ý. Bạn mô tả rõ hơn về chủ đề, giá hoặc tác giả nhé.';
} else {
    $lines = [];
    foreach ($products as $index => $product) {
        $line = ($index + 1) . '. ' . $product['title'] . ' - ' . $product['author'];
        $line .= ' | Giá: $' . number_format((float) $product['price'], 2);
        $line .= ' | Đánh giá: ' . number_format((float) $product['rating'], 1) . '/5';
        $lines[] = $line;
    }
    $responseMessage = "Mình gợi ý cho bạn:\n\n" . implode("\n\n", $lines);
}

// ----- 5. Trả JSON về frontend -----
echo json_encode([
    'status' => 'success',
    'message' => $responseMessage,
    'products' => $products
], JSON_UNESCAPED_UNICODE);

// Đóng kết nối tránh rò rỉ tài nguyên
$conn->close();
?>