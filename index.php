<?php
session_start();
include 'config.php'; // Đảm bảo file config.php có $conn để kết nối DB

// --- Cấu hình Phân trang ---
$products_per_page = 12; // Số sản phẩm hiển thị trên mỗi trang

// Lấy trang hiện tại từ URL, mặc định là 1
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

// Tính toán OFFSET cho câu truy vấn SQL
$offset = ($current_page - 1) * $products_per_page;

// --- 1. Lấy tổng số sản phẩm ---
$total_result = $conn->prepare("SELECT COUNT(*) AS total FROM products");
$total_result->execute();
$total_row = $total_result->get_result()->fetch_assoc();
$total_products = $total_row['total'];
$total_result->close();

// Tính toán tổng số trang
$total_pages = ceil($total_products / $products_per_page);

// Điều chỉnh $current_page nếu nó vượt quá tổng số trang
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
    $offset = ($current_page - 1) * $products_per_page;
} elseif ($total_pages === 0) {
    // Nếu không có sản phẩm nào, đặt $current_page = 1
    $current_page = 1;
}

// --- 2. Lấy danh sách sản phẩm cho trang hiện tại ---
// Sắp xếp theo created_at DESC và giới hạn kết quả
$stmt = $conn->prepare("SELECT product_id, name, price, image FROM products ORDER BY created_at DESC LIMIT ?, ?");
$stmt->bind_param("ii", $offset, $products_per_page);
$stmt->execute();
$resultData = $stmt->get_result();
$products = $resultData->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Kiểm tra xem người dùng đã đăng nhập chưa
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$role = isset($_SESSION['role']) ? $_SESSION['role'] : null;

// Ghi chú: Tôi đã đổi tên biến $result thành $stmt và $resultData để tránh nhầm lẫn.
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trang Chủ - MyShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="#">MyShop</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link active" href="#">Trang chủ</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Sản phẩm</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Giỏ hàng</a></li>

                <?php if(!$role): ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Đăng nhập</a></li>
                <?php elseif($role === 'admin'): ?>
                    <li class="nav-item"><a class="nav-link" href="indexad.php">Quản lý Admin</a></li>
                <?php elseif($role === 'seller'): ?>
                    <li class="nav-item"><a class="nav-link" href="indexse.php">Quản lý sản phẩm</a></li>
                <?php endif; ?>
                
                <?php if($role): ?>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Đăng xuất</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="p-4 bg-white rounded shadow-sm text-center">
        <h2>Chào mừng đến với MyShop</h2>
        <p class="text-muted">Website bán hàng đơn giản sử dụng PHP + Bootstrap</p>
    </div>
</div>

<div class="container mt-4">
    <h3 class="mb-3">Sản phẩm mới nhất</h3>
    <?php if (empty($products)): ?>
        <div class="alert alert-info text-center" role="alert">
            Hiện chưa có sản phẩm nào được bán.
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach($products as $p): ?>
            <div class="col-md-3 mb-4">
                <div class="card shadow-sm h-100">
                    
                    <img src="assets/image/<?php echo htmlspecialchars($p['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($p['name']); ?>" style="height: 200px; object-fit: cover;">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?php echo htmlspecialchars($p['name']); ?></h5>
                        <p class="card-text text-danger fw-bold mt-auto"><?php echo number_format($p['price'],0,',','.'); ?>đ</p>
                        
                        <a href="product_detail.php?product_id=<?php echo $p['product_id']; ?>" class="btn btn-primary w-100 mt-2">Xem chi tiết</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($total_pages > 1): ?>
        <nav aria-label="Product Page Navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                
                <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $current_page - 1; ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $current_page + 1; ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>