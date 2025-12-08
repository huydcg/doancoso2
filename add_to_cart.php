<?php
session_start();
include 'config.php'; // Đảm bảo kết nối DB

// --- 1. KIỂM TRA INPUT VÀ SESSION ---
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
// Thêm 1 sản phẩm mặc định
$quantity_to_add = 1; 

if ($product_id <= 0) {
    // Nếu không có product_id hợp lệ, chuyển hướng về trang chủ
    header('Location: index.php');
    exit;
}

// Khởi tạo giỏ hàng nếu chưa có
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// --- 2. LẤY THÔNG TIN SẢN PHẨM TỪ DB ---
// Truy vấn tên, giá và số lượng tồn kho để xác nhận
$stmt = $conn->prepare("SELECT name, price, quantity FROM products WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$product) {
    $_SESSION['message'] = "<div class='alert alert-danger'>Sản phẩm không tồn tại.</div>";
    header('Location: product_detail.php?product_id=' . $product_id);
    exit;
}

// --- 3. KIỂM TRA TỒN KHO VÀ THÊM VÀO GIỎ HÀNG ---

// Tính toán số lượng hiện tại trong giỏ (nếu có)
$current_cart_quantity = isset($_SESSION['cart'][$product_id]['quantity']) ? $_SESSION['cart'][$product_id]['quantity'] : 0;
$new_quantity = $current_cart_quantity + $quantity_to_add;

// Kiểm tra xem số lượng mới có vượt quá tồn kho không
if ($new_quantity > $product['quantity']) {
    $_SESSION['message'] = "<div class='alert alert-warning'>Xin lỗi, chỉ còn " . $product['quantity'] . " sản phẩm trong kho.</div>";
    header('Location: product_detail.php?product_id=' . $product_id);
    exit;
}

// Thêm/cập nhật sản phẩm vào giỏ hàng
if ($new_quantity <= $product['quantity']) {
    if (isset($_SESSION['cart'][$product_id])) {
        // Nếu sản phẩm đã có, tăng số lượng
        $_SESSION['cart'][$product_id]['quantity'] = $new_quantity;
    } else {
        // Nếu sản phẩm chưa có, thêm mới
        $_SESSION['cart'][$product_id] = [
            'name' => $product['name'],
            'price' => $product['price'],
            'image' => $_POST['image_name'] ?? 'default.jpg', // Lấy tên ảnh từ form (sẽ cập nhật form ở dưới)
            'quantity' => $quantity_to_add
        ];
    }
    
    $_SESSION['message'] = "<div class='alert alert-success'>Đã thêm 1 sản phẩm **" . htmlspecialchars($product['name']) . "** vào giỏ hàng!</div>";
}

// Chuyển hướng về trang chi tiết sản phẩm sau khi thêm thành công (hoặc chuyển về trang giỏ hàng nếu bạn muốn)
header('Location: product_detail.php?product_id=' . $product_id);
exit;
?>