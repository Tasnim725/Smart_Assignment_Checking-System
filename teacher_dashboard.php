<?php
require_once 'config.php';

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

// Fetch statistics
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

// Fetch recent submissions
$submissions_query = "SELECT s.*, a.title as assignment_title, a.batch_number
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

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Poppins', sans-serif; }
        body { background: #f3f4f6; }
        .navbar { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .navbar-brand { font-weight: 700; font-size: 1.5rem; background: linear-gradient(135deg, #6366f1, #8b5cf6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .stat-card { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); border-left: 4px solid #6366f1; }
        .stat-number { font-size: 2.5rem; font-weight: 700; background: linear-gradient(135deg, #6366f1, #8b5cf6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
        .card-header { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; border-radius: 15px 15px 0 0 !important; font-weight: 600; }
        .status-badge { padding: 6px 16px; border-radius: 20px; font-size: 0.875rem; font-weight: 600; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-evaluated { background: #d1fae5; color: #065f46; }
        .btn-primary { background: linear-gradient(135deg, #6366f1, #8b5cf6); border: none; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="teacher_dashboard.php"><i class="bi bi-mortarboard-fill me-2"></i>Smart Assignment Checker</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="teacher_dashboard.php"><i class="bi bi-house-fill me-1"></i>Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="teacher_assignments.php"><i class="bi bi-journal-text me-1"></i>Assignments</a></li>
                    <li class="nav-item"><a class="nav-link" href="teacher_submissions.php"><i class="bi bi-file-earmark-text me-1"></i>Submissions</a></li>
                    <li class="nav-item"><span class="nav-link"><i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8'); ?></span></li>
                    <li class="nav-item"><a class="nav-link text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col"><h2>Welcome, <?php echo htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8'); ?>! 👨‍🏫</h2></div>
        </div>
        
        <?php if (isset($_GET['success']) && $_GET['success'] == 'created'): ?>
        <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle-fill me-2"></i>Assignment created successfully!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        
        <div class="row mb-4">
            <div class="col-md-3 mb-3"><div class="stat-card"><div class="stat-number"><?php echo $stats['total_assignments']; ?></div><div class="text-muted">Assignments</div></div></div>
            <div class="col-md-3 mb-3"><div class="stat-card"><div class="stat-number"><?php echo $stats['total_submissions']; ?></div><div class="text-muted">Submissions</div></div></div>
            <div class="col-md-3 mb-3"><div class="stat-card"><div class="stat-number"><?php echo $stats['evaluated']; ?></div><div class="text-muted">Evaluated</div></div></div>
            <div class="col-md-3 mb-3"><div class="stat-card"><div class="stat-number"><?php echo $stats['avg_score'] ? round($stats['avg_score']) : '-'; ?></div><div class="text-muted">Avg Score</div></div></div>
        </div>
        
        <div class="row">
            <div class="col-lg-5 mb-4">
                <div class="card">
                    <div class="card-header"><i class="bi bi-plus-circle me-2"></i>Create Assignment</div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <div class="mb-3"><label class="form-label fw-semibold">Title *</label><input type="text" class="form-control" name="title" required></div>
                            <div class="mb-3"><label class="form-label fw-semibold">Description *</label><textarea class="form-control" name="description" rows="3" required></textarea></div>
                            <div class="mb-3"><label class="form-label fw-semibold">Deadline *</label><input type="datetime-local" class="form-control" name="deadline" required></div>
                            <div class="mb-3"><label class="form-label fw-semibold">Batch Number *</label><input type="text" class="form-control" name="batch_number" required placeholder="e.g., Batch-2024"></div>
                            <button type="submit" name="create_assignment" class="btn btn-primary w-100"><i class="bi bi-check me-2"></i>Create Assignment</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-7 mb-4">
                <div class="card">
                    <div class="card-header"><i class="bi bi-clock-history me-2"></i>Recent Submissions</div>
                    <div class="card-body p-0">
                        <?php if ($submissions->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead><tr><th>Student</th><th>Assignment</th><th>Batch</th><th>Status</th><th>Action</th></tr></thead>
                                <tbody>
                                    <?php while($sub = $submissions->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($sub['student_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($sub['assignment_title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><strong><?php echo htmlspecialchars($sub['batch_number'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                        <td><span class="status-badge status-<?php echo $sub['evaluation_status']; ?>"><?php echo ucfirst($sub['evaluation_status']); ?></span></td>
                                        <td>
                                            <?php if ($sub['evaluation_status'] == 'pending'): ?>
                                                <a href="evaluate.php?id=<?php echo $sub['id']; ?>" class="btn btn-sm btn-success"><i class="bi bi-robot"></i> Evaluate</a>
                                            <?php else: ?>
                                                <a href="teacher_view_submission.php?id=<?php echo $sub['id']; ?>" class="btn btn-sm btn-primary"><i class="bi bi-eye"></i> View</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5"><i class="bi bi-inbox" style="font-size: 3rem; color: #d1d5db;"></i><h5 class="mt-3">No Submissions Yet</h5></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>