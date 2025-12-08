<?php
include 'config.php';

$message = "";
$message_type = "";

if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $password_input = trim($_POST['password']);
    $fullname = trim($_POST['fullname'] ?? "");
    $email = trim($_POST['email'] ?? "");
    $role = "customer";

    // ========== KIỂM TRA DỮ LIỆU NHẬP ==========
    if ($fullname === "") {
        $message = "Vui lòng nhập tên.";
        $message_type = "danger";
    } elseif ($username === "") {
        $message = "Vui lòng nhập tên đăng nhập.";
        $message_type = "danger";
    } elseif ($password_input === "") {
        $message = "Vui lòng nhập mật khẩu.";
        $message_type = "danger";
    } elseif (strlen($username) < 4) {
        $message = "Tên đăng nhập phải có ít nhất 4 ký tự.";
        $message_type = "danger";
    } elseif (strlen($password_input) < 6) {
        $message = "Mật khẩu phải có ít nhất 6 ký tự.";
        $message_type = "danger";
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Email không hợp lệ.";
        $message_type = "danger";
    } else {

        // ========== KIỂM TRA USERNAME TỒN TẠI ==========
        $check_stmt = $conn->prepare("SELECT username FROM users WHERE username = ? LIMIT 1");
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $message = "Tên đăng nhập đã tồn tại.";
            $message_type = "danger";
        } else {

            // ========== HASH PASSWORD ==========
            $password = password_hash($password_input, PASSWORD_DEFAULT);

            // ========== INSERT ==========
            $stmt = $conn->prepare(
                "INSERT INTO users (fullname, email, username, password, role) VALUES (?, ?, ?, ?, ?)"
            );

            if ($stmt) {
                $stmt->bind_param("sssss", $fullname, $email, $username, $password, $role);

                if ($stmt->execute()) {
                    $message = "Đăng ký thành công!";
                    $message_type = "success";
                } else {
                    $message = "Lỗi: " . $stmt->error;
                    $message_type = "danger";
                }

                $stmt->close();
            }
        }

        $check_stmt->close();
    }
}
?>


<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Đăng ký</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title mb-4">Đăng ký tài khoản</h3>

                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>" role="alert">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" novalidate>
                            <div class="mb-3">
                                <label for="username" class="form-label">Tên đăng nhập</label>
                                <input type="text" class="form-control" id="username" name="username" required
                                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Mật khẩu</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>

                            <div class="mb-3">
                                <label for="fullname" class="form-label">Họ và tên</label>
                                <input type="text" class="form-control" id="fullname" name="fullname" required
                                    value="<?php echo htmlspecialchars($_POST['fullname'] ?? ''); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email (không bắt buộc)</label>
                                <input type="email" class="form-control" id="email" name="email"
                                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>



                            <div class="d-grid">
                                <button type="submit" name="register" class="btn btn-primary">Đăng ký</button>
                            </div>
                        </form>

                        <hr>
                        <p class="small text-muted mb-0">Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS (optional) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>