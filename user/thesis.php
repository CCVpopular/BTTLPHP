<?php
session_start();
require_once '../connect_db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['sinhvien_id'])) {
    header("Location: ../login.php");
    exit;
}

$student_id = $_SESSION['sinhvien_id'];

// Get current thesis information
$thesis_query = "SELECT lv.*, gv.HoTen as TenGiangVien, gv.Email as EmailGiangVien
                FROM LuanVan lv
                LEFT JOIN GiangVien gv ON lv.GiangVienID = gv.GiangVienID
                WHERE lv.SinhVienID = ?
                ORDER BY lv.NgayDangKy DESC
                LIMIT 1";
$stmt = $conn->prepare($thesis_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$thesis = $stmt->get_result()->fetch_assoc();

// Get advisor information
$advisor_query = "SELECT gv.* 
                 FROM GiangVien gv
                 JOIN SinhVienGiangVienHuongDan svgv ON gv.GiangVienID = svgv.GiangVienID
                 WHERE svgv.SinhVienID = ?";
$stmt = $conn->prepare($advisor_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$advisor = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Luận văn</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <h2>Quản lý Luận văn</h2>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (!$advisor): ?>
            <div class="alert alert-warning">
                <strong>Lưu ý:</strong> Bạn chưa được phân công giảng viên hướng dẫn. 
                Vui lòng liên hệ với khoa để được hỗ trợ.
            </div>
        <?php elseif (!$thesis): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">Đăng ký đề tài luận văn</h5>
                    <p>Giảng viên hướng dẫn: <?php echo htmlspecialchars($advisor['HoTen']); ?></p>
                    <a href="thesis_submit.php" class="btn btn-primary">Đăng ký đề tài mới</a>
                </div>
            </div>
        <?php else: ?>
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">Thông tin luận văn</h5>
                </div>
                <div class="card-body">
                    <h4><?php echo htmlspecialchars($thesis['TenDeTai']); ?></h4>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <p><strong>Trạng thái:</strong> 
                                <span class="badge bg-<?php
                                    echo match($thesis['TrangThai']) {
                                        'pending' => 'warning',
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        'completed' => 'info',
                                        default => 'secondary'
                                    };
                                ?>">
                                    <?php
                                        echo match($thesis['TrangThai']) {
                                            'pending' => 'Chờ duyệt',
                                            'approved' => 'Đã duyệt',
                                            'rejected' => 'Từ chối',
                                            'completed' => 'Hoàn thành',
                                            default => 'Không xác định'
                                        };
                                    ?>
                                </span>
                            </p>
                            <p><strong>Ngày đăng ký:</strong> 
                                <?php echo date('d/m/Y', strtotime($thesis['NgayDangKy'])); ?>
                            </p>
                            <p><strong>Giảng viên hướng dẫn:</strong> 
                                <?php echo htmlspecialchars($thesis['TenGiangVien']); ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <?php if ($thesis['NgayPheDuyet']): ?>
                                <p><strong>Ngày phê duyệt:</strong> 
                                    <?php echo date('d/m/Y', strtotime($thesis['NgayPheDuyet'])); ?>
                                </p>
                            <?php endif; ?>
                            <?php if ($thesis['NhanXet']): ?>
                                <p><strong>Nhận xét:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($thesis['NhanXet'])); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mt-3">
                        <p><strong>Mô tả đề tài:</strong></p>
                        <p><?php echo nl2br(htmlspecialchars($thesis['MoTa'])); ?></p>
                    </div>

                    <?php if ($thesis['TrangThai'] === 'approved'): ?>
                        <div class="mt-3">
                            <a href="thesis_progress.php" class="btn btn-primary">
                                Cập nhật tiến độ
                            </a>
                        </div>
                    <?php elseif ($thesis['TrangThai'] === 'rejected'): ?>
                        <div class="mt-3">
                            <a href="thesis_submit.php" class="btn btn-primary">
                                Đăng ký đề tài mới
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
