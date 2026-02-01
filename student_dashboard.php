<?php
require_once 'config.php';

// Check if student is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header("Location: index.php?error=session");
    exit();
}

$conn = getDBConnection();

// Fetch available assignments for student's batch
$student_id = $_SESSION['user_code'];
$batch_number = isset($_SESSION['batch_number']) ? $_SESSION['batch_number'] : '';

$assignments_query = "SELECT * FROM assignments WHERE deadline >= NOW() AND batch_number = ? ORDER BY deadline ASC";
$stmt = $conn->prepare($assignments_query);
$stmt->bind_param("s", $batch_number);
$stmt->execute();
$assignments = $stmt->get_result();
$stmt->close();

// Fetch student's submissions
$submissions_query = "SELECT s.*, a.title as assignment_title 
                      FROM submissions s 
                      JOIN assignments a ON s.assignment_id = a.id 
                      WHERE s.student_id = ? 
                      ORDER BY s.submitted_at DESC";
$stmt = $conn->prepare($submissions_query);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$submissions = $stmt->get_result();
$stmt->close();

// Get student statistics
$stats_query = "SELECT 
    COUNT(*) as total_submissions,
    COUNT(CASE WHEN evaluation_status = 'evaluated' THEN 1 END) as evaluated,
    AVG(CASE WHEN overall_score IS NOT NULL THEN overall_score END) as avg_score
    FROM submissions WHERE student_id = ?";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("s", $student_id);
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
    <title>Student Dashboard - Smart Assignment Checker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>📚 Student Dashboard</h1>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($_SESSION['user_code'], ENT_QUOTES, 'UTF-8'); ?>)</p>
            <p><strong>Batch:</strong> <?php echo htmlspecialchars($batch_number, ENT_QUOTES, 'UTF-8'); ?></p>
        </header>

        <nav>
            <a href="student_dashboard.php" class="active">Dashboard</a>
            <a href="student_submissions.php">My Submissions</a>
            <a href="logout.php" class="logout-btn">Logout 🚪</a>
        </nav>

        <?php if (isset($_GET['success']) && $_GET['success'] == 'submitted'): ?>
        <div class="alert-success">
            ✅ Assignment submitted successfully! Your teacher will evaluate it soon.
        </div>
        <?php endif; ?>

        <div class="content">
            <!-- Statistics -->
            <div class="stats-grid">
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

            <!-- Submit Assignment Form -->
            <div class="form-container">
                <h2>📤 Submit New Assignment</h2>
                
                <form action="submit_assignment.php" method="POST" enctype="multipart/form-data" id="submissionForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="student_id" value="<?php echo $_SESSION['user_code']; ?>">
                    
                    <div class="form-group">
                        <label for="assignment_select">Select Assignment *</label>
                        <select name="assignment_id" id="assignment_select" required>
                            <option value="">-- Choose Assignment --</option>
                            <?php 
                            $assignments->data_seek(0);
                            while($assignment = $assignments->fetch_assoc()): 
                            ?>
                                <option value="<?php echo htmlspecialchars($assignment['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($assignment['title'], ENT_QUOTES, 'UTF-8') . ' (Due: ' . date('M d, Y', strtotime($assignment['deadline'])) . ')'; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <?php if ($assignments->num_rows == 0): ?>
                            <small style="color: #dc3545;">No assignments available for your batch (<?php echo htmlspecialchars($batch_number, ENT_QUOTES, 'UTF-8'); ?>) at the moment.</small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="file_upload">Upload Your Assignment (PDF or Image) *</label>
                        <input type="file" name="assignment_file" id="file_upload" accept=".pdf,image/*" required>
                        <small>Max file size: 10MB. Supported formats: PDF, JPG, PNG</small>
                        <div id="file_preview" style="margin-top: 10px; display: none;">
                            <strong>Selected:</strong> <span id="file_name"></span> (<span id="file_size"></span>)
                        </div>
                    </div>

                    <button type="submit" class="btn-primary" <?php echo $assignments->num_rows == 0 ? 'disabled' : ''; ?>>
                        📤 Submit Assignment
                    </button>
                </form>
            </div>

            <!-- Recent Submissions -->
            <div class="submissions-table">
                <h2>📊 Recent Submissions</h2>
                <?php if ($submissions->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Assignment</th>
                            <th>Submitted</th>
                            <th>Status</th>
                            <th>Score</th>
                            <th>Grade</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $submissions->data_seek(0);
                        $count = 0;
                        while($sub = $submissions->fetch_assoc()): 
                            if ($count >= 5) break;
                            $count++;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sub['assignment_title'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($sub['submitted_at'])); ?></td>
                            <td>
                                <span class="status-badge <?php echo $sub['evaluation_status']; ?>">
                                    <?php echo ucfirst($sub['evaluation_status']); ?>
                                </span>
                            </td>
                            <td><?php echo $sub['overall_score'] !== null ? $sub['overall_score'] : '-'; ?></td>
                            <td><?php echo $sub['grade'] !== null ? htmlspecialchars($sub['grade'], ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                            <td>
                                <?php if ($sub['evaluation_status'] == 'evaluated'): ?>
                                    <a href="view_details.php?id=<?php echo $sub['id']; ?>" class="btn-small">View</a>
                                <?php else: ?>
                                    <span class="text-muted">Pending Evaluation...</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="student_submissions.php" class="btn-secondary">View All Submissions →</a>
                </div>
                <?php else: ?>
                <div class="alert-info" style="text-align: center; padding: 40px;">
                    <h3>🔭 No Submissions Yet</h3>
                    <p>You haven't submitted any assignments yet. Start by submitting your first assignment above!</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
<?php
$conn->close();
?>
