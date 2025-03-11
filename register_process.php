<?php
session_start();
require_once 'connect_db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $fullname = trim($_POST['fullname']);
    $student_id = trim($_POST['student_id']);
    $class = trim($_POST['class']);
    $birthday = trim($_POST['birthday']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate form data
    if (empty($fullname) || empty($student_id) || empty($class) || 
        empty($birthday) || empty($email) || empty($username) || 
        empty($password) || empty($confirm_password)) {
        $_SESSION['register_error'] = "Vui lòng điền đầy đủ thông tin bắt buộc";
        header("Location: register.php");
        exit;
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['register_error'] = "Email không hợp lệ";
        header("Location: register.php");
        exit;
    }
    
    // Check password match
    if ($password !== $confirm_password) {
        $_SESSION['register_error'] = "Mật khẩu xác nhận không khớp";
        header("Location: register.php");
        exit;
    }
    
    // Check if username already exists
    $stmt = $conn->prepare("SELECT UserID FROM Users WHERE Username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $_SESSION['register_error'] = "Tên đăng nhập đã tồn tại";
        header("Location: register.php");
        exit;
    }
    $stmt->close();
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT SinhVienID FROM SinhVien WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $_SESSION['register_error'] = "Email đã được sử dụng";
        header("Location: register.php");
        exit;
    }
    $stmt->close();
    
    // Check if student ID already exists
    $stmt = $conn->prepare("SELECT SinhVienID FROM SinhVien WHERE MaSinhVien = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $_SESSION['register_error'] = "Mã sinh viên đã tồn tại trong hệ thống";
        header("Location: register.php");
        exit;
    }
    $stmt->close();
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Insert student information
        $stmt = $conn->prepare("INSERT INTO SinhVien (HoTen, MaSinhVien, NgaySinh, Lop, Email, SoDienThoai) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $fullname, $student_id, $birthday, $class, $email, $phone);
        $stmt->execute();
        $sinhvien_id = $conn->insert_id;
        $stmt->close();
        
        // Hash password for security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user account (role is now 'student' only)
        $role = 'student';
        $stmt = $conn->prepare("INSERT INTO Users (Username, Password, Email, Role, SinhVienID) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $username, $hashed_password, $email, $role, $sinhvien_id);
        $stmt->execute();
        $stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        $_SESSION['register_success'] = "Đăng ký thành công! Bạn có thể đăng nhập ngay bây giờ.";
        header("Location: login.php");
        exit;
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['register_error'] = "Đã xảy ra lỗi: " . $e->getMessage();
        header("Location: register.php");
        exit;
    }
} else {
    // If not a POST request, redirect to registration page
    header("Location: register.php");
    exit;
}

$conn->close();
?>