<?php
session_start(); // 1. Bắt đầu phiên (session)

// 2. Hủy tất cả các biến session
$_SESSION = array();

// 3. Hủy phiên làm việc (session)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy(); // Phá hủy dữ liệu phiên

// 4. Chuyển hướng người dùng về trang đăng nhập
header("Location: login.php");
exit; // Đảm bảo không có mã nào được thực thi sau lệnh header
?>