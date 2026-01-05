<?php
// Xác thực người dùng đăng ký - đăng nhập - logout
session_start(); // lưu trạng thái đăng nhập
error_reporting(E_ALL);
ini_set('display_errors', 0); // Tắt hiển thị lỗi để tránh output HTML
ini_set('log_errors', 1); // Vẫn log lỗi vào log file
include "config.php"; // Đảm bảo config.php đã kết nối $conn
// Trả dữ liệu về dạng JSON cho frontend (AJAX)
header('Content-Type: application/json');
// Lấy hành động gửi từ client (register / login / logout)
$action = $_POST['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Invalid action'];
// xử lý theo từng hành động 
switch ($action) {
    case 'register':
        // lấy dữ liệu từ form
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'customer'; 
        // kiểm tra dữ liệu rỗng
        if (empty($email) || empty($password)) {
            $response = ['status' => 'error', 'message' => 'Vui lòng điền đầy đủ email và mật khẩu.'];
            break;
        }

        // Kiểm tra email đã tồn tại chưa
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();
        //nếu email tồn tại 
        if ($result->num_rows > 0) {
            $response = ['status' => 'error', 'message' => 'Email đã tồn tại.'];
        } else {
           //mã hóa mật khẩu
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // thêm người dùng mới
            $stmt = $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $email, $hashed_password, $role);
            // thực thi câu lệnh
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
        // truy vấn lấu thông tin users
       $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
//gắn kết quả truy vấn vào biến
$stmt->bind_result($user_id, $hash, $role);
// nếu tìm thấy user
if ($stmt->fetch()) {
    if ($hash !== null && password_verify($password, $hash)) { // so sánh mật khẩu người dùng nhập với mật khẩu đã mã hóa
        //lưu thông tin vào session
        $_SESSION['user_id'] = $user_id;
        $_SESSION['email'] = $email;
        $_SESSION['role'] = $role;
        $response = ['status' => 'success', 'role' => $role]; // trả về thành công và vaitro
    } else {
        $response = ['status' => 'error', 'message' => 'Email hoặc mật khẩu không hợp lệ.'];
    }
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
echo json_encode($response);// Trả JSON về cho client
?>