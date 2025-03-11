<?php
session_start();
require_once '../connect_db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['sinhvien_id'])) {
    header("Location: ../login.php");
    exit;
}

$student_id = $_SESSION['sinhvien_id'];

// Get thesis information
$thesis_query = "SELECT * FROM LuanVan WHERE SinhVienID = ? AND TrangThai = 'approved'";
$stmt = $conn->prepare($thesis_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$thesis = $stmt->get_result()->fetch_assoc();

if (!$thesis) {
    $_SESSION['error'] = "Không tìm thấy đề tài luận văn đã được phê duyệt";
    header("Location: thesis.php");
    exit;
}

// Handle progress update submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("INSERT INTO ThesisProgress (LuanVanID, TieuDe, NoiDung, TrangThai) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $thesis['LuanVanID'], $title, $content, $status);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Cập nhật tiến độ thành công!";
    } else {
        $_SESSION['error'] = "Có lỗi xảy ra khi cập nhật tiến độ";
    }
    
    header("Location: thesis_progress.php");
    exit;
}

// Get progress history
$progress_query = "SELECT * FROM ThesisProgress WHERE LuanVanID = ? ORDER BY NgayCapNhat DESC";
$stmt = $conn->prepare($progress_query);
$stmt->bind_param("i", $thesis['LuanVanID']);
$stmt->execute();
$progress_history = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Theo dõi Tiến độ Luận văn</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <h2>Theo dõi Tiến độ Luận văn</h2>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Cập nhật Tiến độ</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="title" class="form-label">Tiêu đề cập nhật</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="content" class="form-label">Nội dung</label>
                                <textarea class="form-control" id="content" name="content" rows="4" required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="status" class="form-label">Trạng thái</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="in_progress">Đang thực hiện</option>
                                    <option value="completed">Hoàn thành</option>
                                    <option value="delayed">Trễ tiến độ</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Cập nhật</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Lịch sử Tiến độ</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($progress_history->num_rows > 0): ?>
                            <?php while ($progress = $progress_history->fetch_assoc()): ?>
                                <div class="border-bottom mb-3 pb-3">
                                    <h6><?php echo htmlspecialchars($progress['TieuDe']); ?></h6>
                                    <p class="text-muted small">
                                        <?php echo date('d/m/Y H:i', strtotime($progress['NgayCapNhat'])); ?> - 
                                        <span class="badge bg-<?php
                                            echo match($progress['TrangThai']) {
                                                'in_progress' => 'primary',
                                                'completed' => 'success',
                                                'delayed' => 'warning',
                                                default => 'secondary'
                                            };
                                        ?>">
                                            <?php echo htmlspecialchars($progress['TrangThai']); ?>
                                        </span>
                                    </p>
                                    <p><?php echo nl2br(htmlspecialchars($progress['NoiDung'])); ?></p>
                                    <?php if ($progress['NhanXet']): ?>
                                        <div class="alert alert-info">
                                            <strong>Nhận xét:</strong> <?php echo htmlspecialchars($progress['NhanXet']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-muted">Chưa có cập nhật tiến độ nào.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
