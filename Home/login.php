<?php
session_start();
// Nếu đã đăng nhập thì về trang chủ
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - TH Bookstore</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../CSS/login.css">
</head>
<body>

    <video autoplay muted loop class="video-bg">
        <source src="../images/Video Project.mp4" type="video/mp4">
    </video>

    <div class="auth-container">
        <a href="index.php" class="close-btn">&times;</a>

        <div id="login-box">
            <h2>Đăng nhập</h2>
            <div id="loginMsg" class="message"></div>
            
            <form id="formLogin">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="name@example.com" required>
                </div>
                <div class="form-group">
                    <label>Mật khẩu</label>
                    <input type="password" name="password" placeholder="******" required>
                </div>
                <button type="submit" class="btn-submit">Đăng nhập</button>
            </form>

            <div class="switch-text">
                Chưa có tài khoản? <a onclick="showRegister()">Đăng ký ngay</a>
            </div>
        </div>

        <div id="register-box">
            <h2>Register</h2>
            <div id="regMsg" class="message"></div>

            <form id="formRegister">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="you@example.com" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" id="regPwd" required>
                </div>
                <div class="form-group">
                    <label>Xác nhận mật khẩu</label>
                    <input type="password" id="regConfirm" required>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role">
                        <option value="customer">Customer</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <button type="submit" class="btn-submit">Đăng ký</button>
            </form>

            <div class="switch-text">
                Đã có tài khoản? <a onclick="showLogin()">Đăng nhập</a>
            </div>
        </div>
    </div>

    <script src="../javascript/login.js"></script>
</body>
</html>