<?php
include "config.php";

$email = $_POST['email'];
$password = $_POST['password'];
$role = $_POST['role'] ?? 'customer'; // Lấy role từ form, mặc định là customer

// Xác thực role hợp lệ
if (!in_array($role, ['admin', 'customer'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid role']);
    exit;
}

// Hash password luôn theo input người dùng
$password = password_hash($password, PASSWORD_DEFAULT);

// Kiểm tra email đã tồn tại chưa
$check = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Email already exists']);
} else {
    $stmt = $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $password, $role);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $stmt->error]);
    }
    $stmt->close();
}

$conn->close();
?>
