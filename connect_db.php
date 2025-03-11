<?php
// Define a logging function to write messages to a log file instead of displaying them
function logMessage($message) {
    $logFile = __DIR__ . '/database_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

// Database configuration
$host = "localhost";
$username = "root"; // Default MySQL username
$password = ""; // Default MySQL password (empty for XAMPP)
$db_name = "ThesisManagementDB"; // Name of the database to create

// Create connection to MySQL server (without database)
$conn = new mysqli($host, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if database exists, if not create it
$check_db = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$db_name'");

if ($check_db->num_rows == 0) {
    // Database doesn't exist, create it
    $sql = "CREATE DATABASE $db_name";
    if ($conn->query($sql) === TRUE) {
        logMessage("Database created successfully");
    } else {
        logMessage("Error creating database: " . $conn->error);
    }
}

// Connect to the database
$conn = new mysqli($host, $username, $password, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection to database failed: " . $conn->connect_error);
}

logMessage("Connected to database successfully");

// Create SinhVien (Students) table
$sql_sinh_vien = "CREATE TABLE IF NOT EXISTS SinhVien (
    SinhVienID INT AUTO_INCREMENT PRIMARY KEY,
    HoTen NVARCHAR(100) NOT NULL,
    MaSinhVien NVARCHAR(20) NOT NULL,
    NgaySinh DATE NOT NULL,
    Lop NVARCHAR(50) NOT NULL,
    Email NVARCHAR(100) NOT NULL,
    SoDienThoai NVARCHAR(15) NULL,
    UNIQUE KEY (MaSinhVien),
    UNIQUE KEY (Email)
)";

if ($conn->query($sql_sinh_vien) === TRUE) {
    logMessage("Table SinhVien created successfully");
} else {
    logMessage("Error creating SinhVien table: " . $conn->error);
}

// Create GiangVien (Advisors) table
$sql_giang_vien = "CREATE TABLE IF NOT EXISTS GiangVien (
    GiangVienID INT AUTO_INCREMENT PRIMARY KEY,
    HoTen NVARCHAR(100) NOT NULL,
    MaGiangVien NVARCHAR(20) NOT NULL,
    Email NVARCHAR(100) NOT NULL,
    SoDienThoai NVARCHAR(15) NULL,
    BoMon NVARCHAR(100) NOT NULL,
    UNIQUE KEY (MaGiangVien),
    UNIQUE KEY (Email)
)";

if ($conn->query($sql_giang_vien) === TRUE) {
    logMessage("Table GiangVien created successfully");
} else {
    logMessage("Error creating GiangVien table: " . $conn->error);
}

// Create SinhVienGiangVienHuongDan (Student-Advisor Relationship) table
$sql_sv_gv = "CREATE TABLE IF NOT EXISTS SinhVienGiangVienHuongDan (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    SinhVienID INT NOT NULL,
    GiangVienID INT NOT NULL,
    NgayBatDau DATE NOT NULL,
    GhiChu NVARCHAR(255) NULL,
    FOREIGN KEY (SinhVienID) REFERENCES SinhVien(SinhVienID) ON DELETE CASCADE,
    FOREIGN KEY (GiangVienID) REFERENCES GiangVien(GiangVienID) ON DELETE CASCADE
)";

if ($conn->query($sql_sv_gv) === TRUE) {
    logMessage("Table SinhVienGiangVienHuongDan created successfully");
} else {
    logMessage("Error creating SinhVienGiangVienHuongDan table: " . $conn->error);
}

// Create Users table for authentication
$sql_users = "CREATE TABLE IF NOT EXISTS Users (
    UserID INT AUTO_INCREMENT PRIMARY KEY,
    Username NVARCHAR(50) NOT NULL,
    Password NVARCHAR(255) NOT NULL,
    Email NVARCHAR(100) NOT NULL,
    Role ENUM('student', 'admin') NOT NULL DEFAULT 'student',
    SinhVienID INT NULL,
    GiangVienID INT NULL,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (Username),
    UNIQUE KEY (Email),
    FOREIGN KEY (SinhVienID) REFERENCES SinhVien(SinhVienID) ON DELETE SET NULL,
    FOREIGN KEY (GiangVienID) REFERENCES GiangVien(GiangVienID) ON DELETE SET NULL
)";

if ($conn->query($sql_users) === TRUE) {
    logMessage("Table Users created successfully");
} else {
    logMessage("Error creating Users table: " . $conn->error);
}

// $conn->close(); // Uncomment this when you're done with database operations
?>