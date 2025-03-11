
<?php
session_start();
require_once '../connect_db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$student = null;
$error = '';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM SinhVien WHERE SinhVienID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $student = $result->fetch_assoc();
    } else {
        $error = "Không tìm thấy sinh viên";
    }
    $stmt->close();
}

$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa Sinh viên - Quản lý luận văn</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Quản Lý Luận Văn - Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="students.php">Quản lý sinh viên</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="advisors.php">Quản lý giảng viên</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="thesis.php">Quản lý luận văn</a>
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
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php elseif ($student): ?>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h4>Chỉnh sửa thông tin sinh viên</h4>
                        </div>
                        <div class="card-body">
                            <form action="student_process.php" method="POST">
                                <input type="hidden" name="id" value="<?php echo $student['SinhVienID']; ?>">

                                <div class="mb-3">
                                    <label for="hoten" class="form-label">Họ và tên</label>
                                    <input type="text" class="form-control" id="hoten" name="hoten" 
                                           value="<?php echo htmlspecialchars($student['HoTen']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="masv" class="form-label">Mã sinh viên</label>
                                    <input type="text" class="form-control" id="masv" name="masv" 
                                           value="<?php echo htmlspecialchars($student['MaSinhVien']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="ngaysinh" class="form-label">Ngày sinh</label>
                                    <input type="date" class="form-control" id="ngaysinh" name="ngaysinh" 
                                           value="<?php echo $student['NgaySinh']; ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="lop" class="form-label">Lớp</label>
                                    <input type="text" class="form-control" id="lop" name="lop" 
                                           value="<?php echo htmlspecialchars($student['Lop']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($student['Email']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="sodienthoai" class="form-label">Số điện thoại</label>
                                    <input type="tel" class="form-control" id="sodienthoai" name="sodienthoai" 
                                           value="<?php echo htmlspecialchars($student['SoDienThoai']); ?>">
                                </div>

                                <div class="text-end">
                                    <a href="students.php" class="btn btn-secondary">Hủy</a>
                                    <button type="submit" name="action" value="edit" class="btn btn-primary">Cập nhật</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>