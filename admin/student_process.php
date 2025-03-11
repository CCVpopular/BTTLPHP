<?php
session_start();
require_once '../connect_db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
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

        // Validate data
        if (empty($fullname) || empty($student_id) || empty($class) || 
            empty($birthday) || empty($email) || empty($username) || 
            empty($password) || empty($confirm_password)) {
            $_SESSION['error'] = "Vui lòng điền đầy đủ thông tin bắt buộc";
            header("Location: student_add.php");
            exit;
        }

        // Check password match
        if ($password !== $confirm_password) {
            $_SESSION['error'] = "Mật khẩu xác nhận không khớp";
            header("Location: student_add.php");
            exit;
        }

        // Begin transaction
        $conn->begin_transaction();

        try {
            // Check if student ID already exists
            $stmt = $conn->prepare("SELECT SinhVienID FROM SinhVien WHERE MaSinhVien = ? OR Email = ?");
            $stmt->bind_param("ss", $student_id, $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("Mã sinh viên hoặc email đã tồn tại");
            }
            $stmt->close();

            // Check if username exists
            $stmt = $conn->prepare("SELECT UserID FROM Users WHERE Username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("Tên đăng nhập đã tồn tại");
            }
            $stmt->close();

            // Insert student information
            $stmt = $conn->prepare("INSERT INTO SinhVien (HoTen, MaSinhVien, NgaySinh, Lop, Email, SoDienThoai) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $fullname, $student_id, $birthday, $class, $email, $phone);
            $stmt->execute();
            $sinhvien_id = $conn->insert_id;
            $stmt->close();

            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Create user account
            $role = 'student';
            $stmt = $conn->prepare("INSERT INTO Users (Username, Password, Email, Role, SinhVienID) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $username, $hashed_password, $email, $role, $sinhvien_id);
            $stmt->execute();
            $stmt->close();

            // Commit transaction
            $conn->commit();
            header("Location: students.php?msg=added");
            exit;

        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $_SESSION['error'] = "Lỗi: " . $e->getMessage();
            header("Location: student_add.php");
            exit;
        }
    } elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        
        // Check if email exists for other students
        $stmt = $conn->prepare("SELECT SinhVienID FROM SinhVien WHERE Email = ? AND SinhVienID != ?");
        $stmt->bind_param("si", $email, $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $_SESSION['error'] = "Email đã được sử dụng bởi sinh viên khác";
            header("Location: student_edit.php?id=" . $id);
            exit;
        }
        $stmt->close();

        // Update student information
        $stmt = $conn->prepare("UPDATE SinhVien SET HoTen=?, MaSinhVien=?, NgaySinh=?, Lop=?, Email=?, SoDienThoai=? WHERE SinhVienID=?");
        $stmt->bind_param("ssssssi", $hoten, $masv, $ngaysinh, $lop, $email, $sodienthoai, $id);
        
        if ($stmt->execute()) {
            header("Location: students.php?msg=updated");
        } else {
            $_SESSION['error'] = "Lỗi khi cập nhật thông tin sinh viên: " . $stmt->error;
            header("Location: student_edit.php?id=" . $id);
        }
        $stmt->close();
    }
}

header("Location: students.php");
exit;