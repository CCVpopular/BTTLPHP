<?php
session_start();
require_once '../connect_db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'];
    $advisor_id = $_POST['advisor_id'];
    
    // Check if assignment already exists
    $check_stmt = $conn->prepare("SELECT ID FROM SinhVienGiangVienHuongDan WHERE SinhVienID = ?");
    $check_stmt->bind_param("i", $student_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing assignment
        $stmt = $conn->prepare("UPDATE SinhVienGiangVienHuongDan SET GiangVienID = ?, NgayBatDau = CURRENT_DATE WHERE SinhVienID = ?");
        $stmt->bind_param("ii", $advisor_id, $student_id);
    } else {
        // Create new assignment
        $stmt = $conn->prepare("INSERT INTO SinhVienGiangVienHuongDan (SinhVienID, GiangVienID, NgayBatDau) VALUES (?, ?, CURRENT_DATE)");
        $stmt->bind_param("ii", $student_id, $advisor_id);
    }
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Phân công giảng viên hướng dẫn thành công!";
    } else {
        $_SESSION['error'] = "Có lỗi xảy ra khi phân công giảng viên hướng dẫn!";
    }
    
    header("Location: advisor_assignments.php");
    exit;
}

// Get all students and advisors
$students = $conn->query("SELECT * FROM SinhVien ORDER BY HoTen");
$advisors = $conn->query("SELECT * FROM GiangVien ORDER BY HoTen");

// Get current assignments
$assignments = $conn->query("
    SELECT sv.SinhVienID, sv.HoTen AS SinhVienHoTen, sv.MaSinhVien,
           gv.GiangVienID, gv.HoTen AS GiangVienHoTen, gv.MaGiangVien,
           svgv.NgayBatDau
    FROM SinhVien sv
    LEFT JOIN SinhVienGiangVienHuongDan svgv ON sv.SinhVienID = svgv.SinhVienID
    LEFT JOIN GiangVien gv ON svgv.GiangVienID = gv.GiangVienID
    ORDER BY sv.HoTen
");

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phân công Giảng viên hướng dẫn - Hệ thống quản lý luận văn</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Quản Lý Luận Văn - Admin</a>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Phân công Giảng viên hướng dẫn</h2>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">
                <h4>Phân công mới</h4>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <div class="col-md-5">
                        <label for="student_id" class="form-label">Sinh viên</label>
                        <select class="form-select" id="student_id" name="student_id" required>
                            <option value="">Chọn sinh viên...</option>
                            <?php while ($student = $students->fetch_assoc()): ?>
                                <option value="<?php echo $student['SinhVienID']; ?>">
                                    <?php echo htmlspecialchars($student['MaSinhVien'] . ' - ' . $student['HoTen']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-5">
                        <label for="advisor_id" class="form-label">Giảng viên hướng dẫn</label>
                        <select class="form-select" id="advisor_id" name="advisor_id" required>
                            <option value="">Chọn giảng viên...</option>
                            <?php while ($advisor = $advisors->fetch_assoc()): ?>
                                <option value="<?php echo $advisor['GiangVienID']; ?>">
                                    <?php echo htmlspecialchars($advisor['MaGiangVien'] . ' - ' . $advisor['HoTen']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">Phân công</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4>Danh sách phân công hiện tại</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Mã SV</th>
                                <th>Tên sinh viên</th>
                                <th>Giảng viên hướng dẫn</th>
                                <th>Ngày bắt đầu</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $assignments->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['MaSinhVien']); ?></td>
                                <td><?php echo htmlspecialchars($row['SinhVienHoTen']); ?></td>
                                <td>
                                    <?php if ($row['GiangVienHoTen']): ?>
                                        <?php echo htmlspecialchars($row['GiangVienHoTen']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Chưa phân công</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['NgayBatDau']): ?>
                                        <?php echo date('d/m/Y', strtotime($row['NgayBatDau'])); ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['GiangVienID']): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="student_id" value="<?php echo $row['SinhVienID']; ?>">
                                            <input type="hidden" name="advisor_id" value="">
                                            <button type="submit" class="btn btn-sm btn-danger" 
                                                    onclick="return confirm('Bạn có chắc muốn hủy phân công này?')">
                                                Hủy phân công
                                            </button>
                                        </form>
                                    <?php endif; ?>
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
