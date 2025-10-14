<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "config.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $rating = floatval($_POST['rating'] ?? 0);

    if (!empty($_FILES['image']['name'])) {
        // Thư mục uploads nằm cùng cấp file PHP
       $target_dir = "../uploads/";
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0755, true);
}

$fileName = time() . "_" . basename($_FILES["image"]["name"]);
$target_file = $target_dir . $fileName;
$fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

$allowed = ['jpg','jpeg','png','gif'];
if (!in_array($fileType, $allowed)) {
    echo json_encode(["status"=>"error","message"=>"invalid_image"]);
    exit;
}

if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
    $relative_path = $fileName; // chỉ lưu tên file

    $stmt = $conn->prepare("INSERT INTO products (title, author, price, rating, category, image) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssddss", $title, $author, $price, $rating, $category, $relative_path);

            if ($stmt->execute()) {
                echo json_encode(["status"=>"success","message"=>"Product added successfully"]);
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
