<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: signup.php");
    exit();
}

// CSRF check
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    die("Invalid CSRF token");
}

$name = sanitize_input($_POST['name']);
$email = sanitize_input($_POST['email']);
$user_code = sanitize_input($_POST['user_code']);
$department = sanitize_input($_POST['department']);
$password = $_POST['password'];
$user_type = sanitize_input($_POST['user_type']);

// For students, batch_number is required
$batch_number = '';
if ($user_type == 'student') {
    $batch_number = isset($_POST['batch_number']) ? sanitize_input($_POST['batch_number']) : '';
    if (empty($batch_number)) {
        header("Location: signup.php?error=empty");
        exit();
    }
}

if (empty($name) || empty($email) || empty($user_code) || empty($department) || empty($password)) {
    header("Location: signup.php?error=empty");
    exit();
}

// Hash password
$password_hashed = password_hash($password, PASSWORD_DEFAULT);

$conn = getDBConnection();

// Check if email already exists
if ($user_type == 'student') {
    $stmt = $conn->prepare("SELECT id FROM students WHERE email = ?");
} else {
    $stmt = $conn->prepare("SELECT id FROM teachers WHERE email = ?");
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $stmt->close();
    $conn->close();
    header("Location: signup.php?error=exists");
    exit();
}

$stmt->close();

// Insert into database
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
