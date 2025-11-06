<?php
// Bắt đầu session nếu chưa có, để các trang có thể dùng $_SESSION
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$host = "localhost";
$user = "root";
$pass = "";
$db = "thbookstore";



$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
  die("Kết nối thất bại: " . $conn->connect_error);
}
?>
