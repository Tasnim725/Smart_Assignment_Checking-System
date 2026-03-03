<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header("Location: ../index.php?error=session"); exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: student_dashboard.php"); exit(); }
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) die("Error: Invalid security token.");

$student_id   = $_SESSION['user_code'];
$student_name = $_SESSION['user_name'];
$assignment_id = isset($_POST['assignment_id']) ? intval($_POST['assignment_id']) : 0;
if ($assignment_id == 0) die("Error: Please select an assignment.");

$conn = getDBConnection();

if (!isset($_FILES['assignment_file']) || $_FILES['assignment_file']['error'] !== UPLOAD_ERR_OK) {
    $conn->close();
    $err = $_FILES['assignment_file']['error'] ?? -1;
    $msgs = [
        UPLOAD_ERR_INI_SIZE  => "File too large (server limit).",
        UPLOAD_ERR_FORM_SIZE => "File too large (form limit).",
        UPLOAD_ERR_PARTIAL   => "File only partially uploaded.",
        UPLOAD_ERR_NO_FILE   => "No file selected.",
    ];
    die("Error: " . ($msgs[$err] ?? "Upload failed."));
}

$file = $_FILES['assignment_file'];
if ($file['size'] > MAX_FILE_SIZE)                       { $conn->close(); die("Error: File exceeds 10MB limit."); }
if (!in_array($file['type'], ALLOWED_FILE_TYPES))        { $conn->close(); die("Error: Only PDF, JPG, PNG allowed."); }

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$detected = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);
if (!in_array($detected, ALLOWED_FILE_TYPES)) { $conn->close(); die("Error: File type verification failed."); }

$ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$safe_id  = preg_replace('/[^a-zA-Z0-9_-]/', '', $student_id);
$newname  = $safe_id . '_' . $assignment_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$file_path = UPLOAD_DIR . $newname;

if (!move_uploaded_file($file['tmp_name'], $file_path)) { $conn->close(); die("Error: Failed to save uploaded file."); }

$stmt = $conn->prepare("INSERT INTO submissions (assignment_id, student_id, student_name, file_path, file_type, evaluation_status) VALUES (?, ?, ?, ?, ?, 'pending')");
if (!$stmt) { unlink($file_path); $conn->close(); die("Error: DB preparation failed."); }

$rel = 'uploads/' . $newname;
$stmt->bind_param("issss", $assignment_id, $student_id, $student_name, $rel, $file['type']);

if ($stmt->execute()) {
    $stmt->close(); $conn->close();
    header("Location: student_submissions.php?success=submitted"); exit();
} else {
    unlink($file_path); $stmt->close(); $conn->close();
    die("Error: Failed to save submission.");
}
?>
