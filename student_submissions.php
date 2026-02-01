<?php
require_once 'config.php';

// Check if student is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header("Location: index.php?error=session");
    exit();
}

$conn = getDBConnection();
$student_id = $_SESSION['user_code'];

// Fetch all student's submissions
$query = "SELECT s.*, a.title as assignment_title 
          FROM submissions s 
          JOIN assignments a ON s.assignment_id = a.id 
          WHERE s.student_id = ? 
          ORDER BY s.submitted_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$submissions = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Submissions - Smart Assignment Checker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>📊 My Submissions</h1>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8'); ?></p>
        </header>

        <nav>
            <a href="student_dashboard.php">Dashboard</a>
            <a href="student_submissions.php" class="active">My Submissions</a>
            <a href="logout.php" class="logout-btn">Logout 🚪</a>
        </nav>

        <?php if (isset($_GET['success']) && $_GET['success'] == 'submitted'): ?>
        <div class="alert-success">
            ✅ Assignment submitted successfully! Your teacher will evaluate it soon.
        </div>
        <?php endif; ?>

        <div class="content">
            <div class="submissions-table">
                <h2>All My Submissions</h2>
                <?php if ($submissions->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Assignment</th>
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
                            <td><?php echo htmlspecialchars($sub['assignment_title'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($sub['submitted_at'])); ?></td>
                            <td>
                                <span class="status-badge <?php echo $sub['evaluation_status']; ?>">
                                    <?php echo ucfirst($sub['evaluation_status']); ?>
                                </span>
                            </td>
                            <td><?php echo $sub['overall_score'] !== null ? $sub['overall_score'] : '-'; ?>/100</td>
                            <td><strong><?php echo $sub['grade'] !== null ? htmlspecialchars($sub['grade'], ENT_QUOTES, 'UTF-8') : '-'; ?></strong></td>
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
                                <?php if ($sub['evaluation_status'] == 'evaluated'): ?>
                                    <a href="view_details.php?id=<?php echo $sub['id']; ?>" class="btn-small">View Details</a>
                                <?php else: ?>
                                    <span class="text-muted">⏳ Pending Evaluation</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="alert-info" style="text-align: center; padding: 40px;">
                    <h3>🔭 No Submissions Yet</h3>
                    <p>You haven't submitted any assignments yet.</p>
                    <a href="student_dashboard.php" class="btn-primary" style="margin-top: 20px; display: inline-block; width: auto;">Go to Dashboard</a>
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
