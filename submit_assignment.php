<?php
require_once 'config.php';

// Check if student is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header("Location: index.php?error=session");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: student_dashboard.php");
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    die("Error: Invalid security token. Please try again.");
}

// Get logged-in student info
$student_id = $_SESSION['user_code'];
$student_name = $_SESSION['user_name'];

// Get and validate assignment
$assignment_id = isset($_POST['assignment_id']) ? intval($_POST['assignment_id']) : 0;

// Validate required fields
if ($assignment_id == 0) {
    die("Error: Please select an assignment.");
}

// Get database connection
$conn = getDBConnection();

// Handle file upload
if (!isset($_FILES['assignment_file']) || $_FILES['assignment_file']['error'] !== UPLOAD_ERR_OK) {
    $conn->close();
    
    $error_message = "Error: ";
    if (!isset($_FILES['assignment_file'])) {
        $error_message .= "No file was uploaded.";
    } else {
        switch ($_FILES['assignment_file']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $error_message .= "File size exceeds the maximum limit.";
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message .= "File was only partially uploaded.";
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message .= "No file was uploaded.";
                break;
            default:
                $error_message .= "File upload failed.";
        }
    }
    die($error_message);
}

$file = $_FILES['assignment_file'];
$file_name = $file['name'];
$file_tmp = $file['tmp_name'];
$file_size = $file['size'];
$file_type = $file['type'];

// Validate file size
if ($file_size > MAX_FILE_SIZE) {
    $conn->close();
    die("Error: File size (" . round($file_size / 1024 / 1024, 2) . "MB) exceeds the 10MB limit.");
}

// Validate file type
if (!in_array($file_type, ALLOWED_FILE_TYPES)) {
    $conn->close();
    die("Error: Invalid file type '$file_type'. Only PDF and image files (JPG, PNG) are allowed.");
}

// Additional MIME type verification
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$detected_type = finfo_file($finfo, $file_tmp);
finfo_close($finfo);

if (!in_array($detected_type, ALLOWED_FILE_TYPES)) {
    $conn->close();
    die("Error: File type verification failed. The file may be corrupted or have an incorrect extension.");
}

// Generate secure filename
$file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
$safe_student_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $student_id);
$new_filename = $safe_student_id . '_' . $assignment_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $file_extension;
$file_path = UPLOAD_DIR . $new_filename;

// Move uploaded file
if (!move_uploaded_file($file_tmp, $file_path)) {
    $conn->close();
    die("Error: Failed to save uploaded file. Please check directory permissions.");
}

// Insert submission into database (status = 'pending', no evaluation yet)
$stmt = $conn->prepare("INSERT INTO submissions (assignment_id, student_id, student_name, file_path, file_type, evaluation_status) VALUES (?, ?, ?, ?, ?, 'pending')");

if (!$stmt) {
    // Delete uploaded file if database insert fails
    unlink($file_path);
    $conn->close();
    die("Error: Database preparation failed - " . $conn->error);
}

$relative_path = 'uploads/' . $new_filename;
$stmt->bind_param("issss", $assignment_id, $student_id, $student_name, $relative_path, $file_type);

if ($stmt->execute()) {
    $submission_id = $conn->insert_id;
    $stmt->close();
    $conn->close();
    
    // Redirect to student submissions page with success message
    header("Location: student_submissions.php?success=submitted");
    exit();
} else {
    // Delete uploaded file if database insert fails
    unlink($file_path);
    $stmt->close();
    $conn->close();
    die("Error: Failed to save submission to database - " . $stmt->error);
}
?>