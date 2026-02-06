<?php
require_once 'config.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    die("Error: Invalid security token");
}

// Get and sanitize inputs
$email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$user_type = isset($_POST['user_type']) ? sanitize_input($_POST['user_type']) : '';

// Validate inputs
if (empty($email) || empty($password) || empty($user_type)) {
    header("Location: index.php?error=invalid");
    exit();
}

// Validate user type
if (!in_array($user_type, ['student', 'teacher'])) {
    header("Location: index.php?error=invalid");
    exit();
}

$conn = getDBConnection();

// Prepare query based on user type
if ($user_type == 'student') {
    $stmt = $conn->prepare("SELECT id, student_id as user_code, student_name as name, email, password, department, batch_number FROM students WHERE email = ?");
} else {
    $stmt = $conn->prepare("SELECT id, teacher_id as user_code, teacher_name as name, email, password, department FROM teachers WHERE email = ?");
}

if (!$stmt) {
    $conn->close();
    die("Error: Database preparation failed");
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();
$conn->close();

// Verify user exists and password is correct
if ($user && password_verify($password, $user['password'])) {
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_code'] = $user['user_code'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_type'] = $user_type;
    $_SESSION['department'] = $user['department'];
    $_SESSION['login_time'] = time();
    
    // For students, store batch_number
    if ($user_type == 'student' && isset($user['batch_number'])) {
        $_SESSION['batch_number'] = $user['batch_number'];
    }
    
    // Redirect based on user type
    if ($user_type == 'student') {
        header("Location: student_dashboard.php");
    } else {
        header("Location: teacher_dashboard.php");
    }
    exit();
} else {
    // Invalid credentials
    header("Location: index.php?error=invalid");
    exit();
}
?>