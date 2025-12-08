<?php
session_start();
include 'config.php';

// Kiểm tra xem user_id và role đã được set chưa
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$role = isset($_SESSION['role']) ? $_SESSION['role'] : null;

// 1. LẤY PRODUCT ID
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

// Nếu không có ID hợp lệ, chuyển hướng về trang chủ
if ($product_id <= 0) {
    header('Location: index.php');
    exit;
}

// 2. TRUY VẤN THÔNG TIN CHI TIẾT SẢN PHẨM
// Thêm trường 'description' giả định nếu bạn có cột đó trong DB
$stmt = $conn->prepare("SELECT 
    p.product_id, 
    p.name, 
    p.price, 
    p.quantity, 
    p.image,
    p.created_at,
    u.username AS seller_name,
    -- Giả định có cột description trong bảng products
    'Đây là mô tả chi tiết của sản phẩm. Sản phẩm được làm từ chất liệu cao cấp và có sẵn số lượng lớn.' AS description 
FROM products p
JOIN users u ON p.seller_id = u.user_id
WHERE p.product_id = ?");

$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();
$conn->close();

// 3. KIỂM TRA SẢN PHẨM TỒN TẠI
if (!$product) {
    // Sản phẩm không tìm thấy
    header('Location: index.php');
    exit;
}

// Định dạng giá
$formatted_price = number_format($product['price'], 0, ',', '.');
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết Sản phẩm: <?php echo htmlspecialchars($product['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">MyShop</a>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Trang chủ</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Giỏ hàng</a></li>

                <?php if(!$role): ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Đăng nhập</a></li>
                <?php elseif($role === 'admin'): ?>
                    <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Quản lý Admin</a></li>
                <?php elseif($role === 'seller'): ?>
                    <li class="nav-item"><a class="nav-link" href="seller_products.php">Quản lý sản phẩm</a></li>
                <?php endif; ?>
                
                <?php if($role): ?>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Đăng xuất</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <div class="card shadow-lg p-3">
        <div class="row g-0">
            <div class="col-md-5">
                <img src="assets/image/<?php echo htmlspecialchars($product['image']); ?>"
                    class="img-fluid rounded-start border"
                    alt="<?php echo htmlspecialchars($product['name']); ?>"
                    style="object-fit: contain; width: 100%; max-height: 500px;">
            </div>
            
            <div class="col-md-7">
                <div class="card-body">
                    <h1 class="card-title mb-3 fw-bold"><?php echo htmlspecialchars($product['name']); ?></h1>
                    
                    <h2 class="text-danger mb-4">
                        <?php echo $formatted_price; ?>đ
                    </h2>

                    <p class="text-muted">
                        <span class="badge bg-secondary me-2">Đăng bán: <?php echo date('d/m/Y', strtotime($product['created_at'])); ?></span>
                        <span class="badge bg-info">Seller: <?php echo htmlspecialchars($product['seller_name']); ?></span>
                    </p>
                    
                    <h5 class="mt-4">Chi tiết:</h5>
                    <p class="card-text border p-3 rounded bg-light">
                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                    </p>

                    <h5 class="mt-4">Tình trạng kho:</h5>
                    <p class="card-text">
                        <?php if ($product['quantity'] > 5): ?>
                            <span class="text-success fw-bold">Còn hàng (<?php echo $product['quantity']; ?> sản phẩm)</span>
                        <?php elseif ($product['quantity'] > 0): ?>
                            <span class="text-warning fw-bold">Sắp hết hàng (<?php echo $product['quantity']; ?> sản phẩm)</span>
                        <?php else: ?>
                            <span class="text-danger fw-bold">Hết hàng</span>
                        <?php endif; ?>
                    </p>

                    <div class="mt-5">
                        <?php
                        if (isset($_SESSION['message'])) {
                            echo $_SESSION['message'];
                            unset($_SESSION['message']);
                        }
                        ?>

                        <?php if ($product['quantity'] > 0): ?>
                            <form action="add_to_cart.php" method="POST">
                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                <input type="hidden" name="image_name" value="<?php echo htmlspecialchars($product['image']); ?>">
                                
                                <div class="d-flex gap-2">
                                    <button type="submit"
                                            class="btn btn-success flex-fill">
                                        🛒 Thêm vào Giỏ hàng
                                    </button>
                                    
                                    <a href="checkout.php?buy_now=<?php echo $product['product_id']; ?>" 
                                    class="btn btn-warning flex-fill">
                                        ⚡ Mua ngay
                                    </a>
                                </div>
                            </form>
                        <?php else: ?>
                            <button class="btn btn-lg btn-danger w-100" disabled>Hết hàng</button>
                        <?php endif; ?>

                    </div>

                </div>
            </div>
        </div>
    </div>
    
    <div class="mt-4">
        <a href="index.php" class="btn btn-secondary">← Quay lại Trang chủ</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>