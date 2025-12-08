<?php
$servername = "localhost:3307";
$username = "root";   // XAMPP mặc định không có mật khẩu
$password = "";
$database = "shopdb";

// Kết nối
$conn = new mysqli($servername, $username, $password, $database);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
?>
