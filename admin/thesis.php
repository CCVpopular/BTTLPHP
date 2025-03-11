<?php
session_start();
require_once '../connect_db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Get overall statistics
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_thesis,
        SUM(CASE WHEN TrangThai = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN TrangThai = 'approved' THEN 1 ELSE 0 END) as approved_count,
        SUM(CASE WHEN TrangThai = 'completed' THEN 1 ELSE 0 END) as completed_count,
        SUM(CASE WHEN TrangThai = 'rejected' THEN 1 ELSE 0 END) as rejected_count
    FROM LuanVan
")->fetch_assoc();

// Get thesis progress with student and advisor details
$progress_query = "
    SELECT 
        lv.TenDeTai,
        lv.TrangThai,
        lv.NgayDangKy,
        sv.MaSinhVien,
        sv.HoTen as TenSinhVien,
        gv.MaGiangVien,
        gv.HoTen as TenGiangVien,
        (SELECT COUNT(*) FROM ThesisProgress WHERE LuanVanID = lv.LuanVanID) as progress_count,
        (SELECT MAX(NgayCapNhat) FROM ThesisProgress WHERE LuanVanID = lv.LuanVanID) as last_update
    FROM LuanVan lv
    JOIN SinhVien sv ON lv.SinhVienID = sv.SinhVienID
    LEFT JOIN GiangVien gv ON lv.GiangVienID = gv.GiangVienID
    ORDER BY lv.NgayDangKy DESC";

$thesis_list = $conn->query($progress_query);

// Export to CSV if requested
if (isset($_POST['export_csv'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="thesis_progress_report.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, array('Mã SV', 'Tên Sinh viên', 'Tên đề tài', 'GVHD', 'Trạng thái', 'Ngày đăng ký', 'Số cập nhật', 'Cập nhật cuối'));
    
    while ($row = $thesis_list->fetch_assoc()) {
        fputcsv($output, array(
            $row['MaSinhVien'],
            $row['TenSinhVien'],
            $row['TenDeTai'],
            $row['TenGiangVien'],
            $row['TrangThai'],
            $row['NgayDangKy'],
            $row['progress_count'],
            $row['last_update']
        ));
    }
    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo Tiến độ Luận văn - Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Quản Lý Luận Văn - Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="thesis_reports.php">Báo cáo</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Báo cáo Tiến độ Luận văn</h2>
            <form method="POST">
                <button type="submit" name="export_csv" class="btn btn-success">
                    <i class="bi bi-download"></i> Xuất CSV
                </button>
            </form>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Tổng số luận văn</h5>
                        <h2 class="card-text"><?php echo $stats['total_thesis']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <h5 class="card-title">Chờ duyệt</h5>
                        <h2 class="card-text"><?php echo $stats['pending_count']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Đang thực hiện</h5>
                        <h2 class="card-text"><?php echo $stats['approved_count']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Hoàn thành</h5>
                        <h2 class="card-text"><?php echo $stats['completed_count']; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Mã SV</th>
                                <th>Tên Sinh viên</th>
                                <th>Tên đề tài</th>
                                <th>GVHD</th>
                                <th>Trạng thái</th>
                                <th>Ngày đăng ký</th>
                                <th>Số cập nhật</th>
                                <th>Cập nhật cuối</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $thesis_list->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['MaSinhVien']); ?></td>
                                    <td><?php echo htmlspecialchars($row['TenSinhVien']); ?></td>
                                    <td><?php echo htmlspecialchars($row['TenDeTai']); ?></td>
                                    <td><?php echo htmlspecialchars($row['TenGiangVien'] ?? 'Chưa phân công'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php
                                            echo match($row['TrangThai']) {
                                                'pending' => 'warning',
                                                'approved' => 'success',
                                                'completed' => 'info',
                                                'rejected' => 'danger',
                                                default => 'secondary'
                                            };
                                        ?>">
                                            <?php echo htmlspecialchars($row['TrangThai']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($row['NgayDangKy'])); ?></td>
                                    <td><?php echo $row['progress_count']; ?></td>
                                    <td>
                                        <?php echo $row['last_update'] ? date('d/m/Y', strtotime($row['last_update'])) : '-'; ?>
                                    </td>
                                    <td>
                                        <a href="thesis_progress_detail.php?student=<?php echo $row['MaSinhVien']; ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="bi bi-eye"></i> Chi tiết
                                        </a>
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
