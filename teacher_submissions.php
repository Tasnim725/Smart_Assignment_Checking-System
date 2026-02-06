<?php
require_once 'config.php';

// Check if teacher is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'teacher') {
    header("Location: index.php?error=session");
    exit();
}

$conn = getDBConnection();
$teacher_id = $_SESSION['user_code'];

// Get batch filter if set
$batch_filter = isset($_GET['batch']) ? sanitize_input($_GET['batch']) : '';

// Fetch all unique batches for this teacher's assignments
$batch_query = "SELECT DISTINCT batch_number FROM assignments WHERE teacher_id = ? ORDER BY batch_number DESC";
$stmt = $conn->prepare($batch_query);
$stmt->bind_param("s", $teacher_id);
$stmt->execute();
$batches = $stmt->get_result();
$stmt->close();

// Fetch submissions with optional batch filter
if ($batch_filter) {
    $query = "SELECT s.*, a.title as assignment_title, a.batch_number 
              FROM submissions s 
              JOIN assignments a ON s.assignment_id = a.id 
              WHERE a.teacher_id = ? AND a.batch_number = ?
              ORDER BY s.submitted_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $teacher_id, $batch_filter);
} else {
    $query = "SELECT s.*, a.title as assignment_title, a.batch_number 
              FROM submissions s 
              JOIN assignments a ON s.assignment_id = a.id 
              WHERE a.teacher_id = ? 
              ORDER BY s.submitted_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $teacher_id);
}

$stmt->execute();
$submissions = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Submissions - Teacher Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>📊 All Submissions</h1>
        </header>

        <nav>
            <a href="teacher_dashboard.php">Dashboard</a>
            <a href="teacher_assignments.php">My Assignments</a>
            <a href="teacher_submissions.php" class="active">All Submissions</a>
            <a href="logout.php" class="logout-btn">Logout 🚪</a>
        </nav>

        <div class="content">
            <div class="submissions-table">
                <!-- Batch Filter -->
                <div style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center;">
                    <strong>Filter by Batch:</strong>
                    <a href="teacher_submissions.php" class="btn-small <?php echo empty($batch_filter) ? 'active' : ''; ?>" style="<?php echo empty($batch_filter) ? 'background: #667eea;' : 'background: #ccc;'; ?>">All Batches</a>
                    <?php 
                    $batches->data_seek(0);
                    while($batch = $batches->fetch_assoc()): 
                    ?>
                        <a href="teacher_submissions.php?batch=<?php echo urlencode($batch['batch_number']); ?>" 
                           class="btn-small <?php echo $batch_filter == $batch['batch_number'] ? 'active' : ''; ?>"
                           style="<?php echo $batch_filter == $batch['batch_number'] ? 'background: #667eea;' : 'background: #ccc;'; ?>">
                            <?php echo htmlspecialchars($batch['batch_number'], ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    <?php endwhile; ?>
                </div>

                <?php if ($submissions->num_rows > 0): ?>
                <h2><?php echo $batch_filter ? 'Submissions for ' . htmlspecialchars($batch_filter, ENT_QUOTES, 'UTF-8') : 'All Submissions'; ?></h2>
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Assignment</th>
                            <th>Batch</th>
                            <th>Submitted</th>
                            <th>Status</th>
                            <th>Score</th>
                            <th>Grade</th>
                            <th>AI %</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($sub = $submissions->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($sub['student_name'], ENT_QUOTES, 'UTF-8'); ?><br>
                                <small><?php echo htmlspecialchars($sub['student_id'], ENT_QUOTES, 'UTF-8'); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($sub['assignment_title'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><strong><?php echo htmlspecialchars($sub['batch_number'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                            <td><?php echo date('M d, Y H:i', strtotime($sub['submitted_at'])); ?></td>
                            <td>
                                <span class="status-badge <?php echo $sub['evaluation_status']; ?>">
                                    <?php echo ucfirst($sub['evaluation_status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo $sub['overall_score'] !== null ? $sub['overall_score'] : '-'; ?>
                            </td>
                            <td>
                                <?php echo $sub['grade'] !== null ? htmlspecialchars($sub['grade'], ENT_QUOTES, 'UTF-8') : '-'; ?>
                            </td>
                            <td>
                                <?php if ($sub['ai_probability'] !== null): ?>
                                    <span class="ai-badge <?php echo $sub['ai_probability'] > 70 ? 'high' : ($sub['ai_probability'] > 40 ? 'medium' : 'low'); ?>">
                                        <?php echo $sub['ai_probability']; ?>%
                                    </span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($sub['evaluation_status'] == 'pending'): ?>
                                    <a href="evaluate.php?id=<?php echo $sub['id']; ?>" class="btn-small" style="background: #28a745;">Evaluate Now</a>
                                <?php else: ?>
                                    <a href="teacher_view_submission.php?id=<?php echo $sub['id']; ?>" class="btn-small">View & Comment</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="alert-info" style="text-align: center; padding: 40px;">
                    <h3>🔭 No Submissions Yet</h3>
                    <p><?php echo $batch_filter ? "No submissions found for " . htmlspecialchars($batch_filter, ENT_QUOTES, 'UTF-8') : "Students haven't submitted any assignments yet."; ?></p>
                    <a href="teacher_dashboard.php" class="btn-primary" style="margin-top: 20px; display: inline-block; width: auto;">Back to Dashboard</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>