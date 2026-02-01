<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=session");
    exit();
}

$submission_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($submission_id == 0) {
    die("Error: Invalid submission ID");
}

$conn = getDBConnection();

$stmt = $conn->prepare("SELECT s.*, a.title as assignment_title, a.description 
                        FROM submissions s 
                        JOIN assignments a ON s.assignment_id = a.id 
                        WHERE s.id = ?");

if (!$stmt) {
    $conn->close();
    die("Error: Database preparation failed");
}

$stmt->bind_param("i", $submission_id);
$stmt->execute();
$result = $stmt->get_result();
$submission = $result->fetch_assoc();

if (!$submission) {
    $stmt->close();
    $conn->close();
    die("Error: Submission not found");
}

// Check permissions - students can only view their own submissions
if ($_SESSION['user_type'] == 'student' && $submission['student_id'] != $_SESSION['user_code']) {
    $stmt->close();
    $conn->close();
    die("Error: You don't have permission to view this submission");
}

$stmt->close();

// Fetch teacher comments
$stmt = $conn->prepare("SELECT c.*, t.teacher_name 
                        FROM comments c 
                        JOIN teachers t ON c.teacher_id = t.teacher_id 
                        WHERE c.submission_id = ? 
                        ORDER BY c.created_at DESC");
$stmt->bind_param("i", $submission_id);
$stmt->execute();
$comments = $stmt->get_result();
$stmt->close();
$conn->close();

$evaluation = null;
if ($submission['feedback']) {
    $evaluation = json_decode($submission['feedback'], true);
}

// Set navigation based on user type
if ($_SESSION['user_type'] == 'student') {
    $back_link = "student_submissions.php";
    $back_text = "My Submissions";
} else {
    $back_link = "teacher_submissions.php";
    $back_text = "All Submissions";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submission Details - Smart Assignment Checker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>📄 Submission Details</h1>
        </header>

        <nav>
            <?php if ($_SESSION['user_type'] == 'student'): ?>
            <a href="student_dashboard.php">Dashboard</a>
            <a href="student_submissions.php">My Submissions</a>
            <?php else: ?>
            <a href="teacher_dashboard.php">Dashboard</a>
            <a href="teacher_assignments.php">My Assignments</a>
            <a href="teacher_submissions.php">All Submissions</a>
            <?php endif; ?>
            <a href="logout.php" class="logout-btn">Logout 🚪</a>
        </nav>

        <div class="content">
            <div class="submission-details">
                <div class="detail-header">
                    <h2><?php echo htmlspecialchars($submission['assignment_title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <div class="student-info">
                        <p><strong>Student:</strong> <?php echo htmlspecialchars($submission['student_name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($submission['student_id'], ENT_QUOTES, 'UTF-8'); ?>)</p>
                        <p><strong>Submitted:</strong> <?php echo date('F d, Y H:i', strtotime($submission['submitted_at'])); ?></p>
                        <p><strong>Status:</strong> <span class="status-badge <?php echo $submission['evaluation_status']; ?>"><?php echo ucfirst($submission['evaluation_status']); ?></span></p>
                    </div>
                </div>

                <?php if ($evaluation): ?>
                <div class="result-header">
                    <div class="grade-display">
                        <div class="grade-circle"><?php echo htmlspecialchars($evaluation['grade'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="score"><?php echo $evaluation['overall_score']; ?>/100</div>
                    </div>
                </div>

                <div class="result-section">
                    <h3>🤖 AI Content Detection</h3>
                    <?php 
                    $aiClass = $evaluation['ai_probability'] > 70 ? 'high' : 
                              ($evaluation['ai_probability'] > 40 ? 'medium' : 'low');
                    ?>
                    <div class="ai-meter">
                        <div class="ai-bar <?php echo $aiClass; ?>" style="width: <?php echo $evaluation['ai_probability']; ?>%">
                            <?php echo $evaluation['ai_probability']; ?>%
                        </div>
                    </div>
                    <p class="ai-analysis"><?php echo htmlspecialchars($evaluation['ai_analysis'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>

                <div class="result-section">
                    <h3>💪 Strengths</h3>
                    <ul class="feedback-list strengths">
                        <?php foreach ($evaluation['strengths'] as $strength): ?>
                            <li><?php echo htmlspecialchars($strength, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="result-section">
                    <h3>⚠️ Areas for Improvement</h3>
                    <ul class="feedback-list weaknesses">
                        <?php foreach ($evaluation['weaknesses'] as $weakness): ?>
                            <li><?php echo htmlspecialchars($weakness, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="result-section">
                    <h3>❌ Specific Errors Found</h3>
                    <ul class="feedback-list errors">
                        <?php foreach ($evaluation['errors'] as $error): ?>
                            <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="result-section">
                    <h3>📝 AI Detailed Feedback</h3>
                    <p class="detailed-feedback"><?php echo nl2br(htmlspecialchars($evaluation['feedback'], ENT_QUOTES, 'UTF-8')); ?></p>
                </div>

                <div class="result-section">
                    <h3>💡 Suggestions for Improvement</h3>
                    <ul class="feedback-list suggestions">
                        <?php foreach ($evaluation['suggestions'] as $suggestion): ?>
                            <li><?php echo htmlspecialchars($suggestion, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php else: ?>
                <div class="alert-info" style="text-align: center; padding: 40px;">
                    <h3>⏳ Evaluation Pending</h3>
                    <p>This submission has not been evaluated yet.</p>
                    <?php if ($_SESSION['user_type'] == 'teacher'): ?>
                    <a href="evaluate.php?id=<?php echo $submission_id; ?>" class="btn-primary" style="margin-top: 20px; display: inline-block; width: auto;">Start Evaluation</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Teacher Comments Section -->
                <?php if ($comments->num_rows > 0): ?>
                <div class="result-section">
                    <h3>💬 Teacher Comments</h3>
                    <div class="comments-list">
                        <?php while($comment = $comments->fetch_assoc()): ?>
                        <div class="comment-item">
                            <div class="comment-header">
                                <strong><?php echo htmlspecialchars($comment['teacher_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <span class="comment-date"><?php echo date('M d, Y H:i', strtotime($comment['created_at'])); ?></span>
                            </div>
                            <div class="comment-body">
                                <?php echo nl2br(htmlspecialchars($comment['comment'], ENT_QUOTES, 'UTF-8')); ?>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="action-buttons">
                    <a href="<?php echo $back_link; ?>" class="btn-secondary">← Back to <?php echo $back_text; ?></a>
                    <?php if ($_SESSION['user_type'] == 'student'): ?>
                    <a href="student_dashboard.php" class="btn-primary">Go to Dashboard</a>
                    <?php else: ?>
                    <a href="teacher_view_submission.php?id=<?php echo $submission_id; ?>" class="btn-primary">Add Comment</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>