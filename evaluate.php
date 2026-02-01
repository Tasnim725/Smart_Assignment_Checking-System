<?php
require_once 'config.php';

$submission_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($submission_id == 0) {
    die("Error: Invalid submission ID");
}

$conn = getDBConnection();

// Fetch submission details
$stmt = $conn->prepare("SELECT s.*, a.title, a.description FROM submissions s JOIN assignments a ON s.assignment_id = a.id WHERE s.id = ?");

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

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Evaluation - Smart Assignment Checker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>🤖 AI Evaluation in Progress</h1>
        </header>

        <div class="content">
            <div class="evaluation-container">
                <div id="loading" class="loading-screen">
                    <div class="spinner"></div>
                    <h2>Analyzing Your Submission...</h2>
                    <p>Please wait while our AI evaluates your work</p>
                    <div class="progress-steps">
                        <div class="step active">📄 Reading document</div>
                        <div class="step">🔍 Analyzing content</div>
                        <div class="step">🤖 Detecting AI usage</div>
                        <div class="step">✅ Generating feedback</div>
                    </div>
                </div>

                <div id="evaluation-result" style="display: none;">
                    <!-- Results will be inserted here by JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <script>
        const submissionId = <?php echo json_encode($submission_id, JSON_HEX_TAG | JSON_HEX_AMP); ?>;
        const assignmentTitle = <?php echo json_encode($submission['title'], JSON_HEX_TAG | JSON_HEX_AMP); ?>;
        const assignmentDescription = <?php echo json_encode($submission['description'], JSON_HEX_TAG | JSON_HEX_AMP); ?>;
        const studentName = <?php echo json_encode($submission['student_name'], JSON_HEX_TAG | JSON_HEX_AMP); ?>;
        const studentId = <?php echo json_encode($submission['student_id'], JSON_HEX_TAG | JSON_HEX_AMP); ?>;
        const filePath = <?php echo json_encode($submission['file_path'], JSON_HEX_TAG | JSON_HEX_AMP); ?>;
        const fileType = <?php echo json_encode($submission['file_type'], JSON_HEX_TAG | JSON_HEX_AMP); ?>;

        // Simulate progress steps
        let currentStep = 0;
        const steps = document.querySelectorAll('.step');
        const progressInterval = setInterval(() => {
            if (currentStep < steps.length) {
                steps[currentStep].classList.add('active');
                currentStep++;
            }
        }, 2000);

        // Call AI evaluation
        async function evaluateSubmission() {
            try {
                const response = await fetch('ai_evaluate.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        submission_id: submissionId,
                        assignment_title: assignmentTitle,
                        assignment_description: assignmentDescription,
                        student_name: studentName,
                        student_id: studentId,
                        file_path: filePath,
                        file_type: fileType
                    })
                });

                const data = await response.json();
                clearInterval(progressInterval);

                if (data.success) {
                    displayResults(data.evaluation);
                } else {
                    throw new Error(data.error || 'Evaluation failed');
                }
            } catch (error) {
                clearInterval(progressInterval);
                document.getElementById('loading').innerHTML = 
                    '<div class="error-message">' +
                    '<h2>❌ Evaluation Failed</h2>' +
                    '<p>' + escapeHtml(error.message) + '</p>' +
                    '<a href="index.php" class="btn-primary">Return to Home</a>' +
                    '</div>';
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function displayResults(evaluation) {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('evaluation-result').style.display = 'block';

            const aiProbabilityClass = evaluation.ai_probability > 70 ? 'high' : 
                                       evaluation.ai_probability > 40 ? 'medium' : 'low';

            const strengthsHtml = evaluation.strengths.map(s => '<li>' + escapeHtml(s) + '</li>').join('');
            const weaknessesHtml = evaluation.weaknesses.map(w => '<li>' + escapeHtml(w) + '</li>').join('');
            const errorsHtml = evaluation.errors.map(e => '<li>' + escapeHtml(e) + '</li>').join('');
            const suggestionsHtml = evaluation.suggestions.map(s => '<li>' + escapeHtml(s) + '</li>').join('');

            document.getElementById('evaluation-result').innerHTML = 
                '<div class="result-header">' +
                '<h2>✅ Evaluation Complete</h2>' +
                '<div class="grade-display">' +
                '<div class="grade-circle">' + escapeHtml(evaluation.grade) + '</div>' +
                '<div class="score">' + evaluation.overall_score + '/100</div>' +
                '</div>' +
                '</div>' +
                '<div class="result-section">' +
                '<h3>🤖 AI Content Detection</h3>' +
                '<div class="ai-meter">' +
                '<div class="ai-bar ' + aiProbabilityClass + '" style="width: ' + evaluation.ai_probability + '%">' +
                evaluation.ai_probability + '%' +
                '</div>' +
                '</div>' +
                '<p class="ai-analysis">' + escapeHtml(evaluation.ai_analysis) + '</p>' +
                '</div>' +
                '<div class="result-section">' +
                '<h3>💪 Strengths</h3>' +
                '<ul class="feedback-list strengths">' + strengthsHtml + '</ul>' +
                '</div>' +
                '<div class="result-section">' +
                '<h3>⚠️ Areas for Improvement</h3>' +
                '<ul class="feedback-list weaknesses">' + weaknessesHtml + '</ul>' +
                '</div>' +
                '<div class="result-section">' +
                '<h3>❌ Specific Errors Found</h3>' +
                '<ul class="feedback-list errors">' + errorsHtml + '</ul>' +
                '</div>' +
                '<div class="result-section">' +
                '<h3>📝 Detailed Feedback</h3>' +
                '<p class="detailed-feedback">' + escapeHtml(evaluation.feedback) + '</p>' +
                '</div>' +
                '<div class="result-section">' +
                '<h3>💡 Suggestions for Improvement</h3>' +
                '<ul class="feedback-list suggestions">' + suggestionsHtml + '</ul>' +
                '</div>' +
                '<div class="action-buttons">' +
                '<a href="view_submissions.php" class="btn-primary">View All Submissions</a>' +
                '<a href="index.php" class="btn-secondary">Submit Another</a>' +
                '</div>';
        }

        // Start evaluation when page loads
        evaluateSubmission();
    </script>
</body>
</html>