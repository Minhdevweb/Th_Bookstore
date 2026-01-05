<?php
// admin thêm sản phẩm
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0); // Tắt hiển thị lỗi để tránh output HTML
ini_set('log_errors', 1); // Vẫn log lỗi vào log file

include "config.php";
include "session.php"; // quyền admin 

// gọi hàm admin trong session để ktra quyền
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(["status"=>"error","message"=>"Access denied"]); // ngăn người dùng thêm sản phẩm
    exit;
}
// chỉ chấp nhận phương thức POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $rating = floatval($_POST['rating'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);

    // Đảm bảo các cột cần thiết tồn tại
    $conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS stock INT DEFAULT 0");
    $conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1"); // trạng thái hiểnt thị sản phẩm =1 
// chỉ cho phép thêm sp khi có ảnh
    if (!empty($_FILES['image']['name'])) {
       $target_dir = "../uploads/"; // tự tạo thư mục upload nếu chưa tồn tại
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0755, true); // 0755 quyền đọc-ghi-thực thi, true tạo thư mục lồng nhau
}

$fileName = time() . "_" . basename($_FILES["image"]["name"]);  // gắn time để tránh trùng tên file 
$target_file = $target_dir . $fileName;
$fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION)); 

$allowed = ['jpg','jpeg','png','gif']; // chỉ chấp nhận đuôi phai
if (!in_array($fileType, $allowed)) {
    echo json_encode(["status"=>"error","message"=>"invalid_image"]);
    exit;
}
// upload file
if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
    $relative_path = $fileName; // chỉ lưu tên file

    // Đảm bảo sản phẩm mới luôn có is_active = 1 để hiển thị trên blog
    $stmt = $conn->prepare("INSERT INTO products (title, author, price, rating, category, image, stock, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
    $stmt->bind_param("ssddssi", $title, $author, $price, $rating, $category, $relative_path, $stock);

            if ($stmt->execute()) {
                echo json_encode(["status"=>"success","message"=>"Thêm sản phẩm thành công"]);
            } else {
                echo json_encode(["status"=>"error","message"=>"db_error: ".$stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(["status"=>"error","message"=>"upload_error"]);
        }
    } else {
        echo json_encode(["status"=>"error","message"=>"no_image"]);
    }
}
$conn->close();
?>
