<?php
session_start();
require_once '../connect_db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['sinhvien_id'])) {
    header("Location: ../login.php");
    exit;
}

$student_id = $_SESSION['sinhvien_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    
    if (empty($title)) {
        $_SESSION['error'] = "Vui lòng nhập tên đề tài luận văn";
    } else {
        // Get assigned advisor
        $stmt = $conn->prepare("SELECT GiangVienID FROM SinhVienGiangVienHuongDan WHERE SinhVienID = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $advisor = $result->fetch_assoc();
        
        if (!$advisor) {
            $_SESSION['error'] = "Bạn chưa được phân công giảng viên hướng dẫn";
        } else {
            $stmt = $conn->prepare("INSERT INTO LuanVan (TenDeTai, MoTa, SinhVienID, GiangVienID, NgayDangKy) VALUES (?, ?, ?, ?, CURRENT_DATE)");
            $stmt->bind_param("ssii", $title, $description, $student_id, $advisor['GiangVienID']);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Đăng ký đề tài luận văn thành công!";
                header("Location: thesis.php");
                exit;
            } else {
                $_SESSION['error'] = "Có lỗi xảy ra khi đăng ký đề tài";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký Đề tài Luận văn</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h4>Đăng ký Đề tài Luận văn</h4>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?php 
                            echo $_SESSION['error'];
                            unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label for="title" class="form-label">Tên đề tài <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Mô tả đề tài</label>
                        <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Đăng ký đề tài</button>
                        <a href="thesis.php" class="btn btn-secondary">Quay lại</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
