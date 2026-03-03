<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: signup.php");
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    die("Invalid CSRF token");
}

// Get inputs
$name       = sanitize_input($_POST['name']       ?? '');
$email      = sanitize_input($_POST['email']      ?? '');
$user_code  = sanitize_input($_POST['user_code']  ?? '');
$department = sanitize_input($_POST['department'] ?? '');
$password   = $_POST['password']                  ?? '';
$user_type  = sanitize_input($_POST['user_type']  ?? '');

// Batch number (students only)
$batch_number = '';
if ($user_type == 'student') {
    $batch_number = isset($_POST['batch_number']) ? sanitize_input($_POST['batch_number']) : '';
    if (empty($batch_number)) {
        header("Location: signup.php?error=empty");
        exit();
    }
}

// Validate required fields
if (empty($name) || empty($email) || empty($user_code) || empty($department) || empty($password)) {
    header("Location: signup.php?error=empty");
    exit();
}

// Validate user type
if (!in_array($user_type, ['student', 'teacher'])) {
    header("Location: signup.php?error=empty");
    exit();
}

$password_hashed = password_hash($password, PASSWORD_DEFAULT);
$conn = getDBConnection();

// Check if email already exists
if ($user_type == 'student') {
    $chk = $conn->prepare("SELECT id FROM students WHERE email = ?");
} else {
    $chk = $conn->prepare("SELECT id FROM teachers WHERE email = ?");
}

$chk->bind_param("s", $email);
$chk->execute();

if ($chk->get_result()->num_rows > 0) {
    $chk->close();
    $conn->close();
    header("Location: signup.php?error=exists");
    exit();
}
$chk->close();

// Insert new user
if ($user_type == 'student') {
    $stmt = $conn->prepare("INSERT INTO students (student_id, student_name, email, password, department, batch_number) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $user_code, $name, $email, $password_hashed, $department, $batch_number);
} else {
    $stmt = $conn->prepare("INSERT INTO teachers (teacher_id, teacher_name, email, password, department) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $user_code, $name, $email, $password_hashed, $department);
}

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: index.php?success=registered");
    exit();
} else {
    $stmt->close();
    $conn->close();
    header("Location: signup.php?error=fail");
    exit();
}
?>
