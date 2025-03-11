<?php
session_start();
require_once '../connect_db.php';

// Check if user is logged in and is faculty
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit;
}

$faculty_id = $_SESSION['giangvien_id'];
$username = $_SESSION['username'];

// Get assigned students
$students_query = "SELECT sv.*, lv.TenDeTai, lv.TrangThai as ThesisStatus
                  FROM SinhVien sv
                  LEFT JOIN SinhVienGiangVienHuongDan svgv ON sv.SinhVienID = svgv.SinhVienID
                  LEFT JOIN LuanVan lv ON sv.SinhVienID = lv.SinhVienID
                  WHERE svgv.GiangVienID = ?";
$stmt = $conn->prepare($students_query);
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$students = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang Giảng viên - Hệ thống quản lý luận văn</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Quản Lý Luận Văn - Giảng viên</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Sinh viên hướng dẫn</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="thesis_review.php">Duyệt đề tài</a>
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
        <h2>Danh sách sinh viên hướng dẫn</h2>
        
        <div class="card mt-3">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Mã SV</th>
                                <th>Họ và tên</th>
                                <th>Email</th>
                                <th>Đề tài luận văn</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($student = $students->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['MaSinhVien']); ?></td>
                                    <td><?php echo htmlspecialchars($student['HoTen']); ?></td>
                                    <td><?php echo htmlspecialchars($student['Email']); ?></td>
                                    <td>
                                        <?php echo $student['TenDeTai'] ? htmlspecialchars($student['TenDeTai']) : 
                                                '<span class="text-muted">Chưa đăng ký</span>'; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = match($student['ThesisStatus']) {
                                            'pending' => 'warning',
                                            'approved' => 'success',
                                            'rejected' => 'danger',
                                            'completed' => 'info',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $status_class; ?>">
                                            <?php echo $student['ThesisStatus'] ?? 'Chưa có'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="student_progress.php?id=<?php echo $student['SinhVienID']; ?>" 
                                           class="btn btn-sm btn-primary">Xem tiến độ</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
