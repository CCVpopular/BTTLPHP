<?php
session_start();
require_once '../connect_db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit;
}

$faculty_id = $_SESSION['giangvien_id'];

// Handle thesis approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $thesis_id = $_POST['thesis_id'];
    $status = $_POST['status'];
    $comments = trim($_POST['comments']);
    
    $stmt = $conn->prepare("UPDATE LuanVan SET TrangThai = ?, NhanXet = ?, NgayPheDuyet = CURRENT_DATE 
                           WHERE LuanVanID = ? AND GiangVienID = ?");
    $stmt->bind_param("ssii", $status, $comments, $thesis_id, $faculty_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Đã cập nhật trạng thái đề tài luận văn";
    } else {
        $_SESSION['error'] = "Có lỗi xảy ra khi cập nhật";
    }
    
    header("Location: thesis_review.php");
    exit;
}

// Get pending thesis submissions for this faculty member
$query = "SELECT lv.*, sv.HoTen as TenSinhVien, sv.MaSinhVien
          FROM LuanVan lv
          JOIN SinhVien sv ON lv.SinhVienID = sv.SinhVienID
          WHERE lv.GiangVienID = ? AND lv.TrangThai = 'pending'
          ORDER BY lv.NgayDangKy DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duyệt Đề tài Luận văn - Giảng viên</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Quản Lý Luận Văn - Giảng viên</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Sinh viên hướng dẫn</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="thesis_review.php">Duyệt đề tài</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Duyệt Đề tài Luận văn</h2>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if ($result->num_rows > 0): ?>
            <?php while ($thesis = $result->fetch_assoc()): ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title">
                            <?php echo htmlspecialchars($thesis['TenDeTai']); ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Sinh viên:</strong> 
                            <?php echo htmlspecialchars($thesis['TenSinhVien']); ?> 
                            (<?php echo htmlspecialchars($thesis['MaSinhVien']); ?>)
                        </p>
                        <p><strong>Ngày đăng ký:</strong> 
                            <?php echo date('d/m/Y', strtotime($thesis['NgayDangKy'])); ?>
                        </p>
                        <p><strong>Mô tả:</strong><br>
                            <?php echo nl2br(htmlspecialchars($thesis['MoTa'])); ?>
                        </p>
                        
                        <form method="POST" class="mt-3">
                            <input type="hidden" name="thesis_id" value="<?php echo $thesis['LuanVanID']; ?>">
                            
                            <div class="mb-3">
                                <label for="comments" class="form-label">Nhận xét</label>
                                <textarea class="form-control" id="comments" name="comments" rows="3" required></textarea>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" name="status" value="approved" class="btn btn-success">
                                    Phê duyệt
                                </button>
                                <button type="submit" name="status" value="rejected" class="btn btn-danger">
                                    Từ chối
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="alert alert-info">
                Không có đề tài nào đang chờ duyệt.
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
