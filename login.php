<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'config.php';

$message = "";
$message_type = "";

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password_input = $_POST['password'];

    // Lấy user bằng prepared statement
    $stmt = $conn->prepare("SELECT user_id, username, password, role FROM users WHERE username = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($db_id, $db_username, $db_password, $db_role);
            $stmt->fetch();

            if (password_verify($password_input, $db_password)) {
                // Đăng nhập thành công, lưu session
                $_SESSION['user_id'] = $db_id;      // lưu id làm phiên đăng nhập
                $_SESSION['username'] = $db_username;
                $_SESSION['role'] = $db_role;

                // Redirect theo role
                if ($db_role === "customer") {
                    header('Location: index.php');
                    exit;
                } elseif ($db_role === "admin") {
                    header('Location: indexad.php');
                    exit;
                } elseif ($db_role === "seller") {
                    header('Location: indexse.php');
                    exit;
                } else {
                    $message = "Role không hợp lệ.";
                    $message_type = "danger";
                }
            } else {
                $message = "Tên đăng nhập hoặc mật khẩu không đúng.";
                $message_type = "danger";
            }
        } else {
            $message = "Tên đăng nhập hoặc mật khẩu không đúng.";
            $message_type = "danger";
        }

        $stmt->close();
    } else {
        $message = "Lỗi chuẩn bị truy vấn: " . $conn->error;
        $message_type = "danger";
    }
}
?>



<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Đăng nhập</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title mb-4">Đăng nhập</h3>

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

                            <div class="d-grid">
                                <button type="submit" name="login" class="btn btn-primary">Đăng nhập</button>
                            </div>
                        </form>

                        <hr>
                        <p class="small text-muted mb-0">Chưa có tài khoản? <a href="register.php">Đăng ký</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS (optional) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>