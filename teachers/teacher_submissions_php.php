<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'teacher') {
    header("Location: ../index.php?error=session"); exit();
}

$conn       = getDBConnection();
$teacher_id = $_SESSION['user_code'];

$batch_filter      = isset($_GET['batch'])      ? sanitize_input($_GET['batch']) : '';
$assignment_filter = isset($_GET['assignment']) ? intval($_GET['assignment'])    : 0;

// Batches for filter
$stmt = $conn->prepare("SELECT DISTINCT batch_number FROM assignments WHERE teacher_id = ? ORDER BY batch_number DESC");
$stmt->bind_param("s", $teacher_id); $stmt->execute(); $batches = $stmt->get_result(); $stmt->close();

// Assignments for filter dropdown
$stmt = $conn->prepare("SELECT id, title, batch_number FROM assignments WHERE teacher_id = ? ORDER BY created_at DESC");
$stmt->bind_param("s", $teacher_id); $stmt->execute(); $assignments_list = $stmt->get_result(); $stmt->close();

// Build query based on filters
if ($assignment_filter) {
    $stmt = $conn->prepare("SELECT s.*, a.title as assignment_title, a.batch_number FROM submissions s JOIN assignments a ON s.assignment_id = a.id WHERE a.teacher_id = ? AND a.id = ? ORDER BY s.submitted_at DESC");
    $stmt->bind_param("si", $teacher_id, $assignment_filter);
} elseif ($batch_filter) {
    $stmt = $conn->prepare("SELECT s.*, a.title as assignment_title, a.batch_number FROM submissions s JOIN assignments a ON s.assignment_id = a.id WHERE a.teacher_id = ? AND a.batch_number = ? ORDER BY s.submitted_at DESC");
    $stmt->bind_param("ss", $teacher_id, $batch_filter);
} else {
    $stmt = $conn->prepare("SELECT s.*, a.title as assignment_title, a.batch_number FROM submissions s JOIN assignments a ON s.assignment_id = a.id WHERE a.teacher_id = ? ORDER BY s.submitted_at DESC");
    $stmt->bind_param("s", $teacher_id);
}
$stmt->execute(); $submissions = $stmt->get_result(); $stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Submissions - Teacher Dashboard</title>
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
                <li class="nav-item"><a class="nav-link" href="teacher_assignments.php"><i class="bi bi-journal-text me-1"></i>Assignments</a></li>
                <li class="nav-item"><a class="nav-link active" href="teacher_submissions.php"><i class="bi bi-file-earmark-text me-1"></i>Submissions</a></li>
                <li class="nav-item"><a class="nav-link text-danger" href="../logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid py-4">
    <div class="row mb-3"><div class="col"><h2><i class="bi bi-file-earmark-text me-2"></i>All Submissions</h2></div></div>

    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap gap-3 align-items-center">
            <div>
                <strong class="me-2">Batch:</strong>
                <a href="teacher_submissions.php" class="btn btn-sm <?php echo (!$batch_filter && !$assignment_filter) ? 'btn-primary' : 'btn-outline-secondary'; ?> me-1">All</a>
                <?php $batches->data_seek(0); while ($b = $batches->fetch_assoc()): ?>
                <a href="teacher_submissions.php?batch=<?php echo urlencode($b['batch_number']); ?>"
                   class="btn btn-sm <?php echo $batch_filter == $b['batch_number'] ? 'btn-primary' : 'btn-outline-secondary'; ?> me-1">
                    <?php echo htmlspecialchars($b['batch_number'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
                <?php endwhile; ?>
            </div>
            <div>
                <strong class="me-2">Assignment:</strong>
                <select onchange="if(this.value) window.location='teacher_submissions.php?assignment='+this.value; else window.location='teacher_submissions.php';" class="form-select form-select-sm d-inline-block" style="min-width:220px;">
                    <option value="">-- All Assignments --</option>
                    <?php $assignments_list->data_seek(0); while ($a = $assignments_list->fetch_assoc()): ?>
                    <option value="<?php echo $a['id']; ?>" <?php echo $assignment_filter == $a['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($a['title'], ENT_QUOTES, 'UTF-8'); ?> [<?php echo htmlspecialchars($a['batch_number'], ENT_QUOTES, 'UTF-8'); ?>]
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <?php if ($submissions->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Student</th><th>Assignment</th><th>Batch</th><th>Submitted</th><th>Status</th><th>Score</th><th>Grade</th><th>AI %</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php while ($sub = $submissions->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($sub['student_name'], ENT_QUOTES, 'UTF-8'); ?><br><small class="text-muted"><?php echo htmlspecialchars($sub['student_id'], ENT_QUOTES, 'UTF-8'); ?></small></td>
                        <td><?php echo htmlspecialchars($sub['assignment_title'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><strong><?php echo htmlspecialchars($sub['batch_number'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                        <td><?php echo date('M d, Y H:i', strtotime($sub['submitted_at'])); ?></td>
                        <td><span class="status-badge status-<?php echo $sub['evaluation_status']; ?>"><?php echo ucfirst($sub['evaluation_status']); ?></span></td>
                        <td><?php echo $sub['overall_score'] !== null ? $sub['overall_score'] : '-'; ?></td>
                        <td><?php echo $sub['grade'] !== null ? htmlspecialchars($sub['grade'], ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                        <td>
                            <?php if ($sub['ai_probability'] !== null): ?>
                            <span class="ai-badge <?php echo $sub['ai_probability'] > 70 ? 'high' : ($sub['ai_probability'] > 40 ? 'medium' : 'low'); ?>">
                                <?php echo $sub['ai_probability']; ?>%
                            </span>
                            <?php else: ?>-<?php endif; ?>
                        </td>
                        <td>
                            <?php if ($sub['evaluation_status'] == 'pending'): ?>
                            <a href="evaluate.php?id=<?php echo $sub['id']; ?>" class="btn btn-sm btn-success">Evaluate</a>
                            <?php else: ?>
                            <a href="../shared/view_details.php?id=<?php echo $sub['id']; ?>" class="btn btn-sm btn-primary">View</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox" style="font-size:3rem;color:#d1d5db;"></i>
                <h5 class="mt-3">No Submissions Found</h5>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>
