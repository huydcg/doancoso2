<?php
session_start();
include 'config.php'; 

// 1. KIỂM TRA QUYỀN TRUY CẬP VÀ USER ID
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$role = isset($_SESSION['role']) ? $_SESSION['role'] : null;

// Chỉ cho phép Seller xóa sản phẩm
if (!$user_id || $role !== 'seller') {
    $_SESSION['message'] = "<div class='alert alert-danger'>Bạn không có quyền truy cập chức năng này.</div>";
    header('Location: index.php');
    exit;
}

// 2. NHẬN PRODUCT ID TỪ FORM POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];
    $image_name = '';

    // --- Bắt đầu Transaction (Đảm bảo an toàn) ---
    $conn->begin_transaction();

    try {
        // 3. KIỂM TRA QUYỀN SỞ HỮU VÀ LẤY TÊN ẢNH
        // Phải đảm bảo Seller chỉ được xóa sản phẩm của chính mình (WHERE seller_id = ?)
        $stmt = $conn->prepare("SELECT image FROM products WHERE product_id = ? AND seller_id = ?");
        $stmt->bind_param("ii", $product_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Lỗi: Sản phẩm không tồn tại hoặc bạn không có quyền xóa.");
        }
        
        $product = $result->fetch_assoc();
        $image_name = $product['image'];
        $stmt->close();

        // 4. XÓA SẢN PHẨM KHỎI DATABASE
        $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ? AND seller_id = ?");
        $stmt->bind_param("ii", $product_id, $user_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Lỗi DB khi xóa sản phẩm.");
        }
        $stmt->close();
        
        // 5. XÓA FILE ẢNH KHỎI MÁY CHỦ (Nếu có)
        if (!empty($image_name)) {
            $file_path = 'assets/image/' . $image_name;
            // Dùng đường dẫn tuyệt đối để xóa file an toàn hơn
            $file_destination = __DIR__ . '/' . $file_path;
            
            if (file_exists($file_destination) && !unlink($file_destination)) {
                 // Nếu không xóa được file, vẫn coi là thành công vì DB đã xóa
                 // Có thể ghi log lỗi unlink() ở đây nếu cần
            }
        }

        // --- Commit Transaction nếu mọi thứ thành công ---
        $conn->commit();
        $_SESSION['message'] = "<div class='alert alert-success'>Đã xóa sản phẩm thành công!</div>";

    } catch (Exception $e) {
        // --- Rollback nếu có bất kỳ lỗi nào xảy ra ---
        $conn->rollback();
        $_SESSION['message'] = "<div class='alert alert-danger'>" . $e->getMessage() . "</div>";
    }

} else {
    // Nếu truy cập trực tiếp bằng GET hoặc thiếu POST data
    $_SESSION['message'] = "<div class='alert alert-danger'>Yêu cầu xóa không hợp lệ.</div>";
}

$conn->close();
header('Location: indexse.php');
exit;
?>