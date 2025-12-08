<?php
session_start();
include 'config.php';

// 1. KIỂM TRA QUYỀN TRUY CẬP VÀ USER ID
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$role = isset($_SESSION['role']) ? $_SESSION['role'] : null;

// Nếu chưa đăng nhập hoặc không phải là seller, chuyển hướng
if (!$user_id || $role !== 'seller') {
    header('Location: index.php');
    exit;
}

$message = ''; // Biến để lưu thông báo

// 2. XỬ LÝ FORM KHI ĐƯỢC SUBMIT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu từ form
    $name = trim($_POST['name']);
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];

    // 2.1. XỬ LÝ UPLOAD FILE ẢNH
    $image_name = '';
    $upload_ok = true;
    
    // Kiểm tra xem có file ảnh được chọn không
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['image']['tmp_name'];
        $file_name_original = $_FILES['image']['name'];
        
        // Tạo tên file mới duy nhất để tránh trùng lặp
        $file_ext = strtolower(pathinfo($file_name_original, PATHINFO_EXTENSION));
        $image_name = time() . uniqid() . '.' . $file_ext; // Ví dụ: 167888640065e94b15e45c7.jpg
        
        // Đường dẫn thư mục lưu ảnh (Dựa trên cấu trúc thư mục của bạn: assets/image)
        $upload_dir = 'assets/image/';
        $file_destination = $upload_dir . $image_name;
        
        // Kiểm tra loại file (chỉ cho phép ảnh)
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($file_ext, $allowed_types)) {
            $message = "<div class='alert alert-danger'>Chỉ cho phép file JPG, JPEG, PNG & GIF.</div>";
            $upload_ok = false;
        }

        // Kiểm tra kích thước file (ví dụ: tối đa 5MB)
        if ($_FILES['image']['size'] > 5000000) {
            $message = "<div class='alert alert-danger'>Kích thước file quá lớn (tối đa 5MB).</div>";
            $upload_ok = false;
        }

        // Tiến hành tải file lên
        if ($upload_ok) {
            if (!move_uploaded_file($file_tmp, $file_destination)) {
                $message = "<div class='alert alert-danger'>Có lỗi xảy ra khi tải file lên máy chủ.</div>";
                $upload_ok = false;
            }
        }
    } else {
        $message = "<div class='alert alert-danger'>Vui lòng chọn một file ảnh sản phẩm.</div>";
        $upload_ok = false;
    }

    // 2.2. CHÈN DỮ LIỆU VÀO DATABASE NẾU UPLOAD THÀNH CÔNG
    if ($upload_ok) {
        // Chuẩn bị câu truy vấn INSERT
        $stmt = $conn->prepare("INSERT INTO products (seller_id, name, price, quantity, image) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isdis", $user_id, $name, $price, $quantity, $image_name);
        
        if ($stmt->execute()) {
            // Chuyển hướng về trang quản lý sản phẩm sau khi thêm thành công
            $_SESSION['add_success'] = 'Sản phẩm "' . htmlspecialchars($name) . '" đã được thêm thành công!';
            header('Location: indexse.php');
            exit;
        } else {
            // Lỗi DB, có thể xóa file đã tải lên
            if (file_exists($file_destination)) {
                unlink($file_destination);
            }
            $message = "<div class='alert alert-danger'>Lỗi DB khi thêm sản phẩm: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thêm Sản phẩm mới</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">MyShop</a>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Trang chủ</a></li>
                <li class="nav-item"><a class="nav-link active" href="seller_products.php">Quản lý sản phẩm</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Đăng xuất</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <div class="card shadow-sm mx-auto" style="max-width: 600px;">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0">Thêm Sản phẩm mới</h4>
        </div>
        <div class="card-body">
            
            <?php if (!empty($message)) echo $message; ?>

            <form method="POST" action="add_product.php" enctype="multipart/form-data">
                
                <div class="mb-3">
                    <label for="name" class="form-label">Tên Sản phẩm</label>
                    <input type="text" class="form-control" id="name" name="name" required maxlength="100">
                </div>
                
                <div class="mb-3">
                    <label for="price" class="form-label">Giá (VNĐ)</label>
                    <input type="number" step="0.01" class="form-control" id="price" name="price" required min="0.01">
                </div>
                
                <div class="mb-3">
                    <label for="quantity" class="form-label">Số lượng</label>
                    <input type="number" class="form-control" id="quantity" name="quantity" required min="1">
                </div>
                
                <div class="mb-4">
                    <label for="image" class="form-label">Ảnh Sản phẩm</label>
                    <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
                    <div class="form-text">Chọn file ảnh (JPG, PNG, GIF). Ảnh sẽ được lưu vào thư mục **assets/image/**.</div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-success btn-lg">Thêm Sản phẩm</button>
                    <a href="seller_products.php" class="btn btn-outline-secondary">Hủy bỏ / Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>