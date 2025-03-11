<?php
session_start();
require_once 'connect_db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Basic validation
    if (empty($username) || empty($password)) {
        $_SESSION['login_error'] = "Vui lòng điền đầy đủ thông tin đăng nhập";
        header("Location: login.php");
        exit;
    }
    
    // Check if username is an email
    $field = filter_var($username, FILTER_VALIDATE_EMAIL) ? 'Email' : 'Username';
    
    // Prepare statement to avoid SQL injection
    $stmt = $conn->prepare("SELECT UserID, Username, Password, Role, SinhVienID, GiangVienID FROM Users WHERE $field = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['Password'])) {
            // Password is correct, start a new session
            $_SESSION['user_id'] = $user['UserID'];
            $_SESSION['username'] = $user['Username'];
            $_SESSION['role'] = $user['Role'];
            
            if ($user['Role'] == 'student' && $user['SinhVienID']) {
                $_SESSION['sinhvien_id'] = $user['SinhVienID'];
            } elseif ($user['Role'] == 'admin' && $user['GiangVienID']) {
                $_SESSION['giangvien_id'] = $user['GiangVienID'];
            }
            
            // Redirect based on role
            if ($user['Role'] == 'admin') {
                header("Location: admin/index.php");
            } else {
                header("Location: user/index.php");
            }
            exit;
        } else {
            // Wrong password
            $_SESSION['login_error'] = "Mật khẩu không đúng";
            header("Location: login.php");
            exit;
        }
    } else {
        // Username not found
        $_SESSION['login_error'] = "Tài khoản không tồn tại";
        header("Location: login.php");
        exit;
    }
    
    $stmt->close();
} else {
    // If not a POST request, redirect to login page
    header("Location: login.php");
    exit;
}

$conn->close();
?>