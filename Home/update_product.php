<?php
header('Content-Type: application/json');
include "config.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Nhận dữ liệu từ form
    $id = intval($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $rating = floatval($_POST['rating'] ?? 0);

    // Kiểm tra dữ liệu hợp lệ
    if ($id <= 0 || !$title || !$author || !$category) {
        echo json_encode(["status" => "error", "message" => "Invalid input"]);
        exit;
    }

    // Xử lý ảnh mới (nếu có)
    $imagePath = null;
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "../uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);

        $fileName = time() . "_" . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $fileName;
        $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $allowed = ['jpg','jpeg','png','gif'];
        if (!in_array($fileType, $allowed)) {
            echo json_encode(["status" => "error", "message" => "Invalid image format"]);
            exit;
        }

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $imagePath = "../uploads/" . $fileName;
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to upload image"]);
            exit;
        }
    }

    // Nếu có ảnh mới → cập nhật cả ảnh
    if ($imagePath) {
        $stmt = $conn->prepare("UPDATE products SET title=?, author=?, category=?, price=?, rating=?, image=? WHERE id=?");
        $stmt->bind_param("sssdssi", $title, $author, $category, $price, $rating, $imagePath, $id);
    } else {
        // Không có ảnh mới → chỉ cập nhật thông tin khác
        $stmt = $conn->prepare("UPDATE products SET title=?, author=?, category=?, price=?, rating=? WHERE id=?");
        $stmt->bind_param("sssdsi", $title, $author, $category, $price, $rating, $id);
    }

    // Thực thi câu lệnh
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Product updated successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database update failed: " . $stmt->error]);
    }

    $stmt->close();
}
$conn->close();
?>
