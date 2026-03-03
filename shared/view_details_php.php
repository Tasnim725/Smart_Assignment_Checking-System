<?php
require_once '../config.php';

if (!isset($_SESSION['user_id'])) { header("Location: ../index.php?error=session"); exit(); }

$submission_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($submission_id == 0) die("Error: Invalid submission ID");

$conn = getDBConnection();
$stmt = $conn->prepare("SELECT s.*, a.title as assignment_title, a.description, a.teacher_id FROM submissions s JOIN assignments a ON s.assignment_id = a.id WHERE s.id = ?");
if (!$stmt) { $conn->close(); die("Error: DB failed"); }
$stmt->bind_param("i", $submission_id); $stmt->execute();
$submission = $stmt->get_result()->fetch_assoc(); $stmt->close();

if (!$submission) { $conn->close(); die("Error: Submission not found"); }

// Permission check
if ($_SESSION['user_type'] == 'student' && $submission['student_id'] != $_SESSION['user_code']) { $conn->close(); die("Permission denied"); }
if ($_SESSION['user_type'] == 'teacher' && $submission['teacher_id'] != $_SESSION['user_code']) { $conn->close(); die("Permission denied"); }

// Handle comment
$comment_success = false;
if ($_SESSION['user_type'] == 'teacher' && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_comment'])) {
    if (verify_csrf_token($_POST['csrf_token'])) {
        $comment_text = sanitize_input($_POST['comment']);
        $teacher_id   = $_SESSION['user_code'];
        $stmt = $conn->prepare("INSERT INTO comments (submission_id, teacher_id, comment) VALUES (?,?,?)");
        $stmt->bind_param("iss", $submission_id, $teacher_id, $comment_text);
        if ($stmt->execute()) $comment_success = true;
        $stmt->close();
    }
}

// Fetch comments
$stmt = $conn->prepare("SELECT c.*, t.teacher_name FROM comments c JOIN teachers t ON c.teacher_id = t.teacher_id WHERE c.submission_id = ? ORDER BY c.created_at DESC");
$stmt->bind_param("i", $submission_id); $stmt->execute(); $comments = $stmt->get_result(); $stmt->close();
$conn->close();

$evaluation = null;
if ($submission['feedback']) $evaluation = json_decode($submission['feedback'], true);

// Navigation
$back_link = $_SESSION['user_type'] == 'student' ? '../students/student_submissions.php' : '../teachers/teacher_submissions.php';
$back_text = $_SESSION['user_type'] == 'student' ? 'My Submissions' : 'All Submissions';
$dash_link = $_SESSION['user_type'] == 'student' ? '../students/student_dashboard.php' : '../teachers/teacher_dashboard.php';

$csrf_token  = generate_csrf_token();
$file_path   = $submission['file_path'] ?? '';
$file_name   = basename($file_path);
$file_ext    = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
$is_image    = in_array($file_ext, ['jpg','jpeg','png','gif','webp','bmp']);
$is_pdf      = ($file_ext === 'pdf');
$display_path = '../' . $file_path;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submission Details - Smart Assignment Checker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo $dash_link; ?>"><i class="bi bi-mortarboard-fill me-2"></i>Smart Assignment Checker</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="<?php echo $dash_link; ?>"><i class="bi bi-house-fill me-1"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo $back_link; ?>"><?php echo $back_text; ?></a></li>
                <li class="nav-item"><a class="nav-link text-danger" href="../logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-4">
    <?php if ($comment_success): ?>
    <div class="alert alert-success alert-dismissible fade show">Comment added!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <!-- Header -->
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><?php echo htmlspecialchars($submission['assignment_title'], ENT_QUOTES, 'UTF-8'); ?></h5></div>
        <div class="card-body">
            <p class="mb-1"><strong>Student:</strong> <?php echo htmlspecialchars($submission['student_name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($submission['student_id'], ENT_QUOTES, 'UTF-8'); ?>)</p>
            <p class="mb-1"><strong>Submitted:</strong> <?php echo date('F d, Y H:i', strtotime($submission['submitted_at'])); ?></p>
            <p class="mb-0"><strong>Status:</strong> <span class="status-badge status-<?php echo $submission['evaluation_status']; ?>"><?php echo ucfirst($submission['evaluation_status']); ?></span></p>
        </div>
    </div>

    <!-- File Viewer -->
    <?php if (!empty($file_path)): ?>
    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-file-earmark me-2"></i>Submitted File</div>
        <div class="card-body">
            <?php if ($is_image): ?>
            <div class="text-center mb-3">
                <img src="<?php echo htmlspecialchars($display_path, ENT_QUOTES, 'UTF-8'); ?>" alt="Submission" style="max-width:100%;max-height:650px;border:2px solid #e0e0e0;border-radius:10px;">
            </div>
            <?php elseif ($is_pdf): ?>
            <iframe src="<?php echo htmlspecialchars($display_path, ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;height:720px;border:2px solid #e0e0e0;border-radius:10px;" title="PDF"></iframe>
            <?php else: ?>
            <p class="text-muted">Preview not available for this file type.</p>
            <?php endif; ?>
            <a href="<?php echo htmlspecialchars($display_path, ENT_QUOTES, 'UTF-8'); ?>" download="<?php echo htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary mt-2">
                <i class="bi bi-download me-2"></i>Download File
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Evaluation Results -->
    <?php if ($evaluation): ?>
    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-robot me-2"></i>AI Evaluation Results</div>
        <div class="card-body">
            <div class="d-flex justify-content-center align-items-center gap-4 mb-4">
                <div class="grade-circle"><?php echo htmlspecialchars($evaluation['grade'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div style="font-size:2.5rem;font-weight:bold;"><?php echo $evaluation['overall_score']; ?>/100</div>
            </div>

            <?php $aiClass = $evaluation['ai_probability'] > 70 ? 'high' : ($evaluation['ai_probability'] > 40 ? 'medium' : 'low'); ?>
            <h6>AI Content Detection</h6>
            <div class="ai-meter">
                <div class="ai-bar <?php echo $aiClass; ?>" style="width:<?php echo $evaluation['ai_probability']; ?>%">
                    <?php echo $evaluation['ai_probability']; ?>%
                </div>
            </div>
            <p class="mb-4"><?php echo htmlspecialchars($evaluation['ai_analysis'], ENT_QUOTES, 'UTF-8'); ?></p>

            <h6>Strengths</h6>
            <ul class="feedback-list strengths mb-4"><?php foreach ($evaluation['strengths'] as $s): ?><li><?php echo htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); ?></li><?php endforeach; ?></ul>

            <h6>Areas for Improvement</h6>
            <ul class="feedback-list weaknesses mb-4"><?php foreach ($evaluation['weaknesses'] as $w): ?><li><?php echo htmlspecialchars($w, ENT_QUOTES, 'UTF-8'); ?></li><?php endforeach; ?></ul>

            <h6>Errors Found</h6>
            <ul class="feedback-list errors mb-4"><?php foreach ($evaluation['errors'] as $e): ?><li><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></li><?php endforeach; ?></ul>

            <h6>Detailed Feedback</h6>
            <p class="p-3 bg-light rounded mb-4"><?php echo nl2br(htmlspecialchars($evaluation['feedback'], ENT_QUOTES, 'UTF-8')); ?></p>

            <h6>Suggestions</h6>
            <ul class="feedback-list suggestions"><?php foreach ($evaluation['suggestions'] as $sug): ?><li><?php echo htmlspecialchars($sug, ENT_QUOTES, 'UTF-8'); ?></li><?php endforeach; ?></ul>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-info text-center">
        <h5>Evaluation Pending</h5>
        <p class="mb-2">This submission has not been evaluated yet.</p>
        <?php if ($_SESSION['user_type'] == 'teacher'): ?>
        <a href="../teachers/evaluate.php?id=<?php echo $submission_id; ?>" class="btn btn-success">Start AI Evaluation</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Comments -->
    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-chat-text me-2"></i>Teacher Comments</div>
        <div class="card-body">
            <?php if ($comments->num_rows > 0): ?>
            <?php while ($c = $comments->fetch_assoc()): ?>
            <div class="comment-item">
                <div class="comment-header">
                    <strong><?php echo htmlspecialchars($c['teacher_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    <span class="comment-date"><?php echo date('M d, Y H:i', strtotime($c['created_at'])); ?></span>
                </div>
                <div><?php echo nl2br(htmlspecialchars($c['comment'], ENT_QUOTES, 'UTF-8')); ?></div>
            </div>
            <?php endwhile; ?>
            <?php else: ?><p class="text-muted mb-3">No comments yet.</p><?php endif; ?>

            <?php if ($_SESSION['user_type'] == 'teacher'): ?>
            <form method="POST" class="mt-3 border-top pt-3">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Add Comment</label>
                    <textarea name="comment" class="form-control" rows="3" required placeholder="Write your feedback for the student..."></textarea>
                </div>
                <button type="submit" name="add_comment" class="btn btn-primary">Add Comment</button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="d-flex gap-3">
        <a href="<?php echo $back_link; ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i><?php echo $back_text; ?></a>
        <a href="<?php echo $dash_link; ?>" class="btn btn-primary"><i class="bi bi-house me-2"></i>Dashboard</a>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
