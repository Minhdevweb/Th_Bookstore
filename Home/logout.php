<?php
session_start(); // Bắt đầu session để có thể hủy nó

// Xóa tất cả các biến session
$_SESSION = array();

// Nếu muốn hủy session cookie, cần phải xóa nó
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hủy hoàn toàn session
session_destroy();

// Chuyển hướng về trang chủ hoặc trang đăng nhập
header("Location: index.php");
exit();
?>