<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Get student information
$username = $_SESSION['username'];
$student_id = isset($_SESSION['sinhvien_id']) ? $_SESSION['sinhvien_id'] : null;

// If there's no student ID or user is an admin trying to access student page
if (!$student_id || $_SESSION['role'] === 'admin') {
    header("Location: ../login.php");
    exit;
}

// Uncomment and include the database connection file
require_once '../connect_db.php';

// You can load more student information from the database here if needed

$student_info = null;
if ($student_id) {
    // Check if connection exists before using it
    if (!isset($conn) || $conn === null) {
        die("Database connection failed. Please check your connection settings.");
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM SinhVien WHERE SinhVienID = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $student_info = $result->fetch_assoc();
        }
        $stmt->close();
    } catch (Exception $e) {
        die("Database error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang Sinh Viên - Hệ thống quản lý luận văn</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="#">Quản Lý Luận Văn - Sinh Viên</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="thesis.php">Luận văn của tôi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="advisors.php">Giảng viên hướng dẫn</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <?php echo htmlspecialchars($username); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">Thông tin cá nhân</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">Đăng xuất</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h4>Trang cá nhân sinh viên</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($student_info): ?>
                            <h5>Xin chào, <?php echo htmlspecialchars($student_info['HoTen']); ?>!</h5>
                            <p>Chào mừng đến với hệ thống quản lý luận văn.</p>
                            
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-header">Thông tin cá nhân</div>
                                        <div class="card-body">
                                            <p><strong>Họ và tên:</strong> <?php echo htmlspecialchars($student_info['HoTen']); ?></p>
                                            <p><strong>Mã sinh viên:</strong> <?php echo htmlspecialchars($student_info['MaSinhVien']); ?></p>
                                            <p><strong>Lớp:</strong> <?php echo htmlspecialchars($student_info['Lop']); ?></p>
                                            <p><strong>Email:</strong> <?php echo htmlspecialchars($student_info['Email']); ?></p>
                                            <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($student_info['SoDienThoai'] ?: 'Chưa cập nhật'); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">Tài liệu hướng dẫn</div>
                                        <div class="card-body">
                                            <p>Hướng dẫn sử dụng hệ thống quản lý luận văn:</p>
                                            <ul>
                                                <li>Cập nhật thông tin cá nhân trong mục "Thông tin cá nhân"</li>
                                                <li>Xem thông tin luận văn trong mục "Luận văn của tôi"</li>
                                                <li>Liên hệ với giảng viên hướng dẫn trong mục "Giảng viên hướng dẫn"</li>
                                            </ul>
                                            <a href="#" class="btn btn-primary">Xem hướng dẫn chi tiết</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                Không tìm thấy thông tin sinh viên. Vui lòng liên hệ quản trị viên.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>