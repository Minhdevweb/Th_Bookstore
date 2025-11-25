<?php
// Bắt đầu session nếu chưa có
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Cấu hình database
$host = "localhost";
$user = "root"; 
$pass = "";
$db = "thbookstore";

// Kết nối database
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    // Ghi log lỗi
    error_log("Database Connection Failed: " . $conn->connect_error);
    
    // Hiển thị thông báo thân thiện
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        die(json_encode(["status" => "error", "message" => "System error. Please try again later."]));
    } else {
        die("System maintenance in progress. Please try again later.");
    }
}

// Thiết lập charset
$conn->set_charset("utf8mb4");
date_default_timezone_set('Asia/Ho_Chi_Minh');
?>