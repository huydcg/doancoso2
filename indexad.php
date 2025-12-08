<?php
session_start();
include 'config.php'; // Chứa $conn để kết nối DB

// 1. KIỂM TRA QUYỀN TRUY CẬP (Guardrail)
$role = isset($_SESSION['role']) ? $_SESSION['role'] : null;

// --- 2. XỬ LÝ THAY ĐỔI ROLE (FORM SUBMISSION) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id']) && isset($_POST['new_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['new_role'];

    // Đảm bảo role mới là một trong các giá trị hợp lệ
    $valid_roles = ['user', 'seller', 'admin'];
    if (in_array($new_role, $valid_roles)) {
        
        // Chuẩn bị câu truy vấn UPDATE an toàn
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE user_id = ?");
        $stmt->bind_param("si", $new_role, $user_id);
        
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Cập nhật vai trò thành công!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Lỗi cập nhật: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } else {
        $message = "<div class='alert alert-warning'>Vai trò không hợp lệ.</div>";
    }
}

// --- 3. LẤY DANH SÁCH NGƯỜI DÙNG ---
$result = $conn->prepare("SELECT user_id, username, fullname, email, role, created_at FROM users ORDER BY user_id ASC");
$result->execute();
$users = $result->get_result()->fetch_all(MYSQLI_ASSOC);
$result->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Admin - Người dùng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="indexad.php">Admin Panel</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link active" href="indexad.php">Quản lý Người dùng</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php">Về Trang chủ</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Đăng xuất</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <h2 class="mb-4">Quản lý Vai trò Người dùng</h2>

    <?php if (isset($message)) echo $message; ?>

    <div class="table-responsive bg-white p-3 rounded shadow-sm">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Fullname</th>
                    <th>Email</th>
                    <th>Role Hiện tại</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['fullname']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td>
                        <span class="badge bg-<?php echo ($user['role'] === 'admin') ? 'danger' : (($user['role'] === 'seller') ? 'warning' : 'info'); ?>">
                            <?php echo htmlspecialchars(ucfirst($user['role'])); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                        <form method="POST" action="indexad.php" class="d-flex align-items-center">
                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                            
                            <select name="new_role" class="form-select form-select-sm me-2">
                                <option value="user" <?php echo ($user['role'] === 'user') ? 'selected' : ''; ?>>User</option>
                                <option value="seller" <?php echo ($user['role'] === 'seller') ? 'selected' : ''; ?>>Seller</option>
                                <option value="admin" <?php echo ($user['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                            </select>
                            
                            <button type="submit" class="btn btn-sm btn-success">Cập nhật</button>
                        </form>
                        <?php else: ?>
                        <span class="text-muted">Bạn</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>