
<?php
session_start();
require_once '../connect_db.php';

// Check authentication and role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$advisor = null;
$isEdit = false;

// If ID is provided, fetch advisor data for editing
if (isset($_GET['id'])) {
    $isEdit = true;
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM GiangVien WHERE GiangVienID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $advisor = $result->fetch_assoc();
    
    if (!$advisor) {
        $_SESSION['error'] = "Không tìm thấy giảng viên!";
        header("Location: advisors.php");
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hoTen = trim($_POST['hoTen']);
    $maGV = trim($_POST['maGV']);
    $email = trim($_POST['email']);
    $soDienThoai = trim($_POST['soDienThoai']);
    $boMon = trim($_POST['boMon']);
    
    // Validate required fields
    if (empty($hoTen) || empty($maGV) || empty($email) || empty($boMon)) {
        $_SESSION['error'] = "Vui lòng điền đầy đủ thông tin bắt buộc!";
    } else {
        // Check for duplicate MaGiangVien and Email
        $checkQuery = "SELECT GiangVienID FROM GiangVien WHERE (MaGiangVien = ? OR Email = ?)";
        if ($isEdit) {
            $checkQuery .= " AND GiangVienID != ?";
        }
        
        $stmt = $conn->prepare($checkQuery);
        if ($isEdit) {
            $stmt->bind_param("ssi", $maGV, $email, $id);
        } else {
            $stmt->bind_param("ss", $maGV, $email);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $_SESSION['error'] = "Mã giảng viên hoặc email đã tồn tại!";
        } else {
            if ($isEdit) {
                $stmt = $conn->prepare("UPDATE GiangVien SET HoTen=?, MaGiangVien=?, Email=?, SoDienThoai=?, BoMon=? WHERE GiangVienID=?");
                $stmt->bind_param("sssssi", $hoTen, $maGV, $email, $soDienThoai, $boMon, $id);
            } else {
                $stmt = $conn->prepare("INSERT INTO GiangVien (HoTen, MaGiangVien, Email, SoDienThoai, BoMon) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $hoTen, $maGV, $email, $soDienThoai, $boMon);
            }
            
            if ($stmt->execute()) {
                $_SESSION['success'] = $isEdit ? "Cập nhật thành công!" : "Thêm giảng viên thành công!";
                header("Location: advisors.php");
                exit;
            } else {
                $_SESSION['error'] = "Có lỗi xảy ra!";
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
    <title><?php echo $isEdit ? 'Sửa' : 'Thêm'; ?> Giảng viên - Hệ thống quản lý luận văn</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Quản Lý Luận Văn - Admin</a>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0"><?php echo $isEdit ? 'Sửa' : 'Thêm'; ?> giảng viên</h4>
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
                        <label for="hoTen" class="form-label">Họ và tên <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="hoTen" name="hoTen" required
                               value="<?php echo $advisor ? htmlspecialchars($advisor['HoTen']) : ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label for="maGV" class="form-label">Mã giảng viên <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="maGV" name="maGV" required
                               value="<?php echo $advisor ? htmlspecialchars($advisor['MaGiangVien']) : ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?php echo $advisor ? htmlspecialchars($advisor['Email']) : ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label for="soDienThoai" class="form-label">Số điện thoại</label>
                        <input type="tel" class="form-control" id="soDienThoai" name="soDienThoai"
                               value="<?php echo $advisor ? htmlspecialchars($advisor['SoDienThoai']) : ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label for="boMon" class="form-label">Bộ môn <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="boMon" name="boMon" required
                               value="<?php echo $advisor ? htmlspecialchars($advisor['BoMon']) : ''; ?>">
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><?php echo $isEdit ? 'Cập nhật' : 'Thêm mới'; ?></button>
                        <a href="advisors.php" class="btn btn-secondary">Hủy</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>