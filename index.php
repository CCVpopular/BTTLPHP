
<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Redirect based on user role
if ($_SESSION['role'] === 'admin') {
    header("Location: admin/index.php");
    exit;
} else {
    header("Location: user/index.php");
    exit;
}
?>