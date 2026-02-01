<?php
require_once 'config.php';

// Check if teacher is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'teacher') {
    header("Location: index.php?error=session");
    exit();
}

$conn = getDBConnection();
$teacher_id = $_SESSION['user_code'];

// Handle new assignment creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_assignment'])) {
    if (verify_csrf_token($_POST['csrf_token'])) {
        $title = sanitize_input($_POST['title']);
        $description = sanitize_input($_POST['description']);
        $deadline = $_POST['deadline'];
        $batch_number = sanitize_input($_POST['batch_number']);
        
        $stmt = $conn->prepare("INSERT INTO assignments (title, description, deadline, teacher_id, batch_number) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $title, $description, $deadline, $teacher_id, $batch_number);
        
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: teacher_dashboard.php?success=created");
            exit();
        }
        $stmt->close();
    }
}

// Fetch teacher's assignments with batch info
$assignments_query = "SELECT * FROM assignments WHERE teacher_id = ? ORDER BY created_at DESC LIMIT 5";
$stmt = $conn->prepare($assignments_query);
$stmt->bind_param("s", $teacher_id);
$stmt->execute();
$assignments = $stmt->get_result();
$stmt->close();

// Fetch recent submissions for teacher's assignments
$submissions_query = "SELECT s.*, a.title as assignment_title, a.teacher_id, a.batch_number
                      FROM submissions s 
                      JOIN assignments a ON s.assignment_id = a.id 
                      WHERE a.teacher_id = ? 
                      ORDER BY s.submitted_at DESC 
                      LIMIT 10";
$stmt = $conn->prepare($submissions_query);
$stmt->bind_param("s", $teacher_id);
$stmt->execute();
$submissions = $stmt->get_result();
$stmt->close();

// Get statistics
$stats_query = "SELECT 
    COUNT(DISTINCT a.id) as total_assignments,
    COUNT(s.id) as total_submissions,
    COUNT(CASE WHEN s.evaluation_status = 'evaluated' THEN 1 END) as evaluated,
    AVG(CASE WHEN s.overall_score IS NOT NULL THEN s.overall_score END) as avg_score
    FROM assignments a
    LEFT JOIN submissions s ON a.id = s.assignment_id
    WHERE a.teacher_id = ?";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("s", $teacher_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Generate CSRF token
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Smart Assignment Checker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>👨‍🏫 Teacher Dashboard</h1>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($_SESSION['user_code'], ENT_QUOTES, 'UTF-8'); ?>)</p>
        </header>

        <nav>
            <a href="teacher_dashboard.php" class="active">Dashboard</a>
            <a href="teacher_assignments.php">My Assignments</a>
            <a href="teacher_submissions.php">All Submissions</a>
            <a href="logout.php" class="logout-btn">Logout 🚪</a>
        </nav>

        <?php if (isset($_GET['success'])): ?>
        <div class="alert-success">
            <?php 
            if ($_GET['success'] == 'created') echo "✅ Assignment created successfully!";
            if ($_GET['success'] == 'commented') echo "✅ Comment added successfully!";
            ?>
        </div>
        <?php endif; ?>

        <div class="content">
            <!-- Statistics Dashboard -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_assignments']; ?></div>
                    <div class="stat-label">My Assignments</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_submissions']; ?></div>
                    <div class="stat-label">Total Submissions</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['evaluated']; ?></div>
                    <div class="stat-label">Evaluated</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['avg_score'] ? round($stats['avg_score']) : '-'; ?></div>
                    <div class="stat-label">Average Score</div>
                </div>
            </div>

            <!-- Create New Assignment -->
            <div class="admin-section">
                <h2>➕ Create New Assignment</h2>
                <form method="POST" class="admin-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-group">
                        <label>Assignment Title *</label>
                        <input type="text" name="title" required placeholder="e.g., Data Structures Homework 1">
                    </div>
                    
                    <div class="form-group">
                        <label>Description *</label>
                        <textarea name="description" rows="4" required placeholder="Enter assignment description and requirements..."></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Deadline *</label>
                            <input type="datetime-local" name="deadline" required>
                        </div>
                        <div class="form-group">
                            <label>Batch Number *</label>
                            <input type="text" name="batch_number" required placeholder="e.g., Batch-2024">
                            <small>Specify which batch this assignment is for</small>
                        </div>
                    </div>
                    
                    <button type="submit" name="create_assignment" class="btn-primary">Create Assignment</button>
                </form>
            </div>

            <!-- Recent Submissions -->
            <div class="submissions-table">
                <h2>📊 Recent Submissions</h2>
                <?php if ($submissions->num_rows > 0): ?>
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
                            <td><?php echo $sub['overall_score'] !== null ? $sub['overall_score'] : '-'; ?></td>
                            <td><?php echo $sub['grade'] !== null ? htmlspecialchars($sub['grade'], ENT_QUOTES, 'UTF-8') : '-'; ?></td>
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
                <div style="text-align: center; margin-top: 20px;">
                    <a href="teacher_submissions.php" class="btn-secondary">View All Submissions →</a>
                </div>
                <?php else: ?>
                <div class="alert-info" style="text-align: center; padding: 40px;">
                    <h3>🔭 No Submissions Yet</h3>
                    <p>Students haven't submitted any assignments yet.</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- My Recent Assignments -->
            <div class="admin-section">
                <h2>📚 My Recent Assignments</h2>
                <?php if ($assignments->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Batch</th>
                            <th>Deadline</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $assignments->data_seek(0);
                        while($assignment = $assignments->fetch_assoc()): 
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($assignment['title'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                            <td><strong><?php echo htmlspecialchars($assignment['batch_number'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                            <td>
                                <?php 
                                $deadline = strtotime($assignment['deadline']);
                                $now = time();
                                $is_expired = $deadline < $now;
                                ?>
                                <span style="color: <?php echo $is_expired ? '#dc3545' : '#28a745'; ?>">
                                    <?php echo date('M d, Y H:i', $deadline); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($assignment['created_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="teacher_assignments.php" class="btn-secondary">View All Assignments →</a>
                </div>
                <?php else: ?>
                <p class="alert-info">No assignments created yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>
