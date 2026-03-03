<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'teacher') {
    header("Location: ../index.php?error=session"); exit();
}

$conn       = getDBConnection();
$teacher_id = $_SESSION['user_code'];

$stmt = $conn->prepare("SELECT a.*, COUNT(s.id) as submission_count, COUNT(CASE WHEN s.evaluation_status='evaluated' THEN 1 END) as evaluated_count FROM assignments a LEFT JOIN submissions s ON a.id = s.assignment_id WHERE a.teacher_id = ? GROUP BY a.id ORDER BY a.created_at DESC");
$stmt->bind_param("s", $teacher_id); $stmt->execute(); $assignments = $stmt->get_result(); $stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Assignments - Teacher Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="teacher_dashboard.php"><i class="bi bi-mortarboard-fill me-2"></i>Smart Assignment Checker</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="teacher_dashboard.php"><i class="bi bi-house-fill me-1"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link active" href="teacher_assignments.php"><i class="bi bi-journal-text me-1"></i>Assignments</a></li>
                <li class="nav-item"><a class="nav-link" href="teacher_submissions.php"><i class="bi bi-file-earmark-text me-1"></i>Submissions</a></li>
                <li class="nav-item"><a class="nav-link text-danger" href="../logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid py-4">
    <div class="row mb-4"><div class="col"><h2><i class="bi bi-journal-text me-2"></i>My Assignments</h2></div></div>
    <div class="card">
        <div class="card-header"><i class="bi bi-list me-2"></i>All Assignments</div>
        <div class="card-body p-0">
            <?php if ($assignments->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Title</th><th>Batch</th><th>Deadline</th><th>Submissions</th><th>Evaluated</th><th>Created</th></tr></thead>
                    <tbody>
                    <?php while ($a = $assignments->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($a['title'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
                            <small class="text-muted"><?php echo htmlspecialchars(substr($a['description'], 0, 80), ENT_QUOTES, 'UTF-8'); ?>...</small>
                        </td>
                        <td><span class="badge bg-primary"><?php echo htmlspecialchars($a['batch_number'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td>
                            <?php $exp = strtotime($a['deadline']) < time(); ?>
                            <span class="text-<?php echo $exp ? 'danger' : 'success'; ?>">
                                <?php echo date('M d, Y H:i', strtotime($a['deadline'])); ?>
                                <?php if ($exp): ?><br><small>(Expired)</small><?php endif; ?>
                            </span>
                        </td>
                        <td><strong><?php echo $a['submission_count']; ?></strong></td>
                        <td><strong><?php echo $a['evaluated_count']; ?></strong></td>
                        <td><?php echo date('M d, Y', strtotime($a['created_at'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox" style="font-size:3rem;color:#d1d5db;"></i>
                <h5 class="mt-3">No Assignments Yet</h5>
                <a href="teacher_dashboard.php" class="btn btn-primary mt-3">Create First Assignment</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>
