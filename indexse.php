<?php
session_start();
include 'config.php'; // Đảm bảo file config.php có $conn

// 1. KIỂM TRA QUYỀN TRUY CẬP VÀ USER ID
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$role = isset($_SESSION['role']) ? $_SESSION['role'] : null;

// Nếu chưa đăng nhập hoặc không phải là seller, chuyển hướng
if (!$user_id || $role !== 'seller') {
    header('Location: index.php');
    exit;
}

// --- LOGIC PHÂN TRANG (Tương tự index.php) ---
$products_per_page = 10; // Có thể điều chỉnh số lượng sản phẩm/trang cho bảng quản lý

// Lấy trang hiện tại từ URL
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $products_per_page;

// --- 2. Lấy tổng số sản phẩm CỦA SELLER HIỆN TẠI ---
$total_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM products WHERE seller_id = ?");
$total_stmt->bind_param("i", $user_id);
$total_stmt->execute();
$total_row = $total_stmt->get_result()->fetch_assoc();
$total_products = $total_row['total'];
$total_stmt->close();

$total_pages = ceil($total_products / $products_per_page);

// Điều chỉnh $current_page nếu cần
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
    $offset = ($current_page - 1) * $products_per_page;
} elseif ($total_pages === 0) {
    $current_page = 1;
}

// --- 3. Lấy danh sách sản phẩm CỦA SELLER HIỆN TẠI ---
// Thêm điều kiện WHERE seller_id = ? để chỉ lấy sản phẩm của người bán này
$stmt = $conn->prepare("SELECT product_id, name, price, quantity, image, created_at FROM products WHERE seller_id = ? ORDER BY created_at DESC LIMIT ?, ?");
$stmt->bind_param("iii", $user_id, $offset, $products_per_page);
$stmt->execute();
$resultData = $stmt->get_result();
$products = $resultData->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Lưu ý: Hiện tại chưa có logic Xóa/Sửa. Phần này chỉ tập trung vào hiển thị.
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Sản phẩm - MyShop Seller</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">MyShop</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Trang chủ</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Giỏ hàng</a></li>

                <?php if($role === 'seller'): ?>
                    <li class="nav-item"><a class="nav-link active" href="indexse.php">Quản lý sản phẩm</a></li>
                <?php endif; ?>
                
                <?php if($role): ?>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Đăng xuất</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Quản lý Sản phẩm của tôi</h2>
        <a href="add_product.php" class="btn btn-success">➕ Thêm Sản phẩm mới</a>
    </div>

    <?php // if (isset($_SESSION['message'])): ?>
        <?php
        // Sau này bạn có thể thêm logic hiển thị message từ session ở đây
        // echo '<div class="alert alert-info">' . $_SESSION['message'] . '</div>';
        // unset($_SESSION['message']);
        ?>
    <?php // endif; ?>

    <?php if (empty($products)): ?>
        <div class="alert alert-warning text-center" role="alert">
            Bạn chưa đăng bán sản phẩm nào. Hãy thêm một sản phẩm mới!
        </div>
    <?php else: ?>
        <div class="table-responsive bg-white p-3 rounded shadow-sm">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ảnh</th>
                        <th>Tên Sản phẩm</th>
                        <th>Giá</th>
                        <th>Số lượng</th>
                        <th>Ngày tạo</th>
                        <th style="width: 150px;">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($p['product_id']); ?></td>
                        <td>
                            <img src="assets/image/<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" style="width: 50px; height: 50px; object-fit: cover;">
                        </td>
                        <td><?php echo htmlspecialchars($p['name']); ?></td>
                        <td><?php echo number_format($p['price'], 0, ',', '.'); ?>đ</td>
                        <td>
                            <span class="badge bg-<?php echo ($p['quantity'] > 0) ? 'success' : 'danger'; ?>">
                                <?php echo htmlspecialchars($p['quantity']); ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($p['created_at'])); ?></td>
                        <td>
                            <a href="edit_product.php?id=<?php echo $p['product_id']; ?>" class="btn btn-sm btn-primary me-2">Sửa</a>
                            
                            <form method="POST" action="delete_product.php" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?');">
                                <input type="hidden" name="product_id" value="<?php echo $p['product_id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Xóa</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
            <nav aria-label="Product Page Navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    
                    <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $current_page - 1; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    
                    <?php
                    // Logic hiển thị tối đa 5 trang gần nhất
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);

                    for($i = $start_page; $i <= $end_page; $i++):
                    ?>
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
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>