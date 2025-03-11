<?php
require_once 'connect_db.php';

// Function to log messages
function logSampleData($message) {
    echo $message . "<br>";
}

// Start transaction
$conn->begin_transaction();

try {
    // Insert admin user
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO Users (Username, Password, Email, Role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $admin_password, $email, $role);

    $username = "admin";
    $email = "admin@university.edu";
    $role = "admin";
    $stmt->execute();
    logSampleData("Added admin user");

    // Arrays to store generated IDs
    $student_ids = [];
    $faculty_ids = [];

    // Insert sample students
    $students = [
        ['Nguyễn Văn An', 'B2012345', '2002-05-15', 'CNTT2020A', 'van.an@student.edu', '0901234567'],
        ['Trần Thị Bình', 'B2012346', '2002-08-20', 'CNTT2020B', 'thi.binh@student.edu', '0901234568'],
        ['Lê Hoàng Cường', 'B2012347', '2002-03-10', 'CNTT2020A', 'hoang.cuong@student.edu', '0901234569']
    ];

    $stmt = $conn->prepare("INSERT INTO SinhVien (HoTen, MaSinhVien, NgaySinh, Lop, Email, SoDienThoai) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $hoten, $masv, $ngaysinh, $lop, $email, $sodienthoai);

    foreach ($students as $student) {
        list($hoten, $masv, $ngaysinh, $lop, $email, $sodienthoai) = $student;
        $stmt->execute();
        $sinhvien_id = $conn->insert_id;
        $student_ids[] = $sinhvien_id;  // Store generated ID

        // Create user account for student
        $username = strtolower(explode('@', $email)[0]);
        $password = password_hash($masv, PASSWORD_DEFAULT);
        $role = 'student';

        $stmt2 = $conn->prepare("INSERT INTO Users (Username, Password, Email, Role, SinhVienID) VALUES (?, ?, ?, ?, ?)");
        $stmt2->bind_param("ssssi", $username, $password, $email, $role, $sinhvien_id);
        $stmt2->execute();
        logSampleData("Added student: $hoten");
    }

    // Insert sample faculty members
    $faculty = [
        ['TS. Phạm Văn Đức', 'GV001', 'van.duc@faculty.edu', '0909123456', 'Công nghệ phần mềm'],
        ['PGS.TS. Lê Thị Em', 'GV002', 'thi.em@faculty.edu', '0909123457', 'Hệ thống thông tin'],
        ['TS. Trần Văn Fx', 'GV003', 'van.fx@faculty.edu', '0909123458', 'Khoa học máy tính']
    ];

    $stmt = $conn->prepare("INSERT INTO GiangVien (HoTen, MaGiangVien, Email, SoDienThoai, BoMon) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $hoten, $magv, $email, $sodienthoai, $bomon);

    foreach ($faculty as $teacher) {
        list($hoten, $magv, $email, $sodienthoai, $bomon) = $teacher;
        $stmt->execute();
        $giangvien_id = $conn->insert_id;
        $faculty_ids[] = $giangvien_id;  // Store generated ID

        // Create user account for faculty
        $username = strtolower(explode('@', $email)[0]);
        $password = password_hash($magv, PASSWORD_DEFAULT);
        $role = 'faculty';

        $stmt2 = $conn->prepare("INSERT INTO Users (Username, Password, Email, Role, GiangVienID) VALUES (?, ?, ?, ?, ?)");
        $stmt2->bind_param("ssssi", $username, $password, $email, $role, $giangvien_id);
        $stmt2->execute();
        logSampleData("Added faculty: $hoten");
    }

    // Assign advisors to students using the stored IDs
    $stmt = $conn->prepare("INSERT INTO SinhVienGiangVienHuongDan (SinhVienID, GiangVienID, NgayBatDau) VALUES (?, ?, CURRENT_DATE)");
    
    // Pair each student with a faculty member
    for ($i = 0; $i < min(count($student_ids), count($faculty_ids)); $i++) {
        $stmt->bind_param("ii", $student_ids[$i], $faculty_ids[$i]);
        $stmt->execute();
        logSampleData("Assigned student {$student_ids[$i]} to faculty {$faculty_ids[$i]}");
    }

    // Commit transaction
    $conn->commit();
    echo "<div class='alert alert-success'>Đã thêm dữ liệu mẫu thành công!</div>";

} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    echo "<div class='alert alert-danger'>Lỗi: " . $e->getMessage() . "</div>";
}

// Close connection
$conn->close();

echo "<p>Thông tin đăng nhập mẫu:</p>";
echo "<ul>";
echo "<li>Admin: username = admin, password = admin123</li>";
echo "<li>Sinh viên: username = van.an, password = B2012345</li>";
echo "<li>Giảng viên: username = van.duc, password = GV001</li>";
echo "</ul>";
?>
