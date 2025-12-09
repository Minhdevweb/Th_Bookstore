<?php
session_start();
include "config.php"; // Đảm bảo config.php đã kết nối $conn

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Invalid action'];

switch ($action) {
    case 'register':
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'customer'; 

        if (empty($email) || empty($password)) {
            $response = ['status' => 'error', 'message' => 'Vui lòng điền đầy đủ email và mật khẩu.'];
            break;
        }

        // Kiểm tra email đã tồn tại chưa
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $response = ['status' => 'error', 'message' => 'Email đã tồn tại.'];
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $stmt = $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $email, $hashed_password, $role);

            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'Đăng ký thành công!'];
            } else {
                $response = ['status' => 'error', 'message' => 'Lỗi CSDL: ' . $stmt->error];
            }
            $stmt->close();
        }
        break;

    case 'login':
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $response = ['status' => 'error', 'message' => 'Vui lòng điền đầy đủ email và mật khẩu.'];
            break;
        }

        $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($user_id, $hash, $role);

        if ($stmt->fetch() && password_verify($password, $hash)) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = $role;
            $response = ['status' => 'success', 'role' => $role];
        } else {
            $response = ['status' => 'error', 'message' => 'Email hoặc mật khẩu không hợp lệ.'];
        }
        $stmt->close();
        break;

    case 'logout':
        session_destroy();
        // Không cần chuyển hướng, chỉ cần trả về thông báo thành công
        $response = ['status' => 'success', 'message' => 'Đăng xuất thành công.'];
        break;
        
    default:
        // Đã được set ở đầu file
        break;
}

$conn->close();
echo json_encode($response);
?>