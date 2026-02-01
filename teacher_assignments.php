<?php
require_once 'config.php';

// Check if teacher is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'teacher') {
    header("Location: index.php?error=session");
    exit();
}

$conn = getDBConnection();
$teacher_id = $_SESSION['user_code'];

// Fetch all teacher's assignments with submission counts
$query = "SELECT a.*, 
          COUNT(s.id) as submission_count,
          COUNT(CASE WHEN s.evaluation_status = 'evaluated' THEN 1 END) as evaluated_count
          FROM assignments a
          LEFT JOIN submissions s ON a.id = s.assignment_id
          WHERE a.teacher_id = ?
          GROUP BY a.id
          ORDER BY a.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $teacher_id);
$stmt->execute();
$assignments = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Assignments - Teacher Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>📚 My Assignments</h1>
        </header>

        <nav>
            <a href="teacher_dashboard.php">Dashboard</a>
            <a href="teacher_assignments.php" class="active">My Assignments</a>
            <a href="teacher_submissions.php">All Submissions</a>
            <a href="logout.php" class="logout-btn">Logout 🚪</a>
        </nav>

        <div class="content">
            <div class="admin-section">
                <h2>All My Assignments</h2>
                <?php if ($assignments->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Batch</th>
                            <th>Description</th>
                            <th>Deadline</th>
                            <th>Submissions</th>
                            <th>Evaluated</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($assignment = $assignments->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($assignment['title'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                            <td><strong><?php echo htmlspecialchars($assignment['batch_number'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                            <td><?php echo htmlspecialchars(substr($assignment['description'], 0, 100), ENT_QUOTES, 'UTF-8') . (strlen($assignment['description']) > 100 ? '...' : ''); ?></td>
                            <td>
                                <?php 
                                $deadline = strtotime($assignment['deadline']);
                                $now = time();
                                $is_expired = $deadline < $now;
                                ?>
                                <span style="color: <?php echo $is_expired ? '#dc3545' : '#28a745'; ?>">
                                    <?php echo date('M d, Y H:i', $deadline); ?>
                                    <?php if ($is_expired): ?>
                                        <br><small>(Expired)</small>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td><strong><?php echo $assignment['submission_count']; ?></strong></td>
                            <td><strong><?php echo $assignment['evaluated_count']; ?></strong></td>
                            <td><?php echo date('M d, Y', strtotime($assignment['created_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="alert-info" style="text-align: center; padding: 40px;">
                    <h3>🔭 No Assignments Yet</h3>
                    <p>You haven't created any assignments yet.</p>
                    <a href="teacher_dashboard.php" class="btn-primary" style="margin-top: 20px; display: inline-block; width: auto;">Create Your First Assignment</a>
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
