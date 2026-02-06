<?php
require_once 'config.php';

header('Content-Type: application/json');

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request data'
    ]);
    exit();
}

$submission_id = isset($input['submission_id']) ? intval($input['submission_id']) : 0;
$assignment_title = isset($input['assignment_title']) ? $input['assignment_title'] : '';
$assignment_description = isset($input['assignment_description']) ? $input['assignment_description'] : '';
$student_name = isset($input['student_name']) ? $input['student_name'] : '';
$student_id = isset($input['student_id']) ? $input['student_id'] : '';
$file_path = isset($input['file_path']) ? $input['file_path'] : '';
$file_type = isset($input['file_type']) ? $input['file_type'] : '';

// Validate inputs
if ($submission_id == 0 || empty($file_path)) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing required parameters'
    ]);
    exit();
}

// Check if API key is configured
if (!defined('GEMINI_API_KEY') || empty(GEMINI_API_KEY) || GEMINI_API_KEY == 'AIzaSyA_qOzbqHc5OV1kXLxUY4Opvb01EH0yPo0') {
    echo json_encode([
        'success' => false,
        'error' => 'API key not configured. Please set your Gemini API key in config.php'
    ]);
    exit();
}

try {
    // Read file and convert to base64
    if (!file_exists($file_path)) {
        throw new Exception("File not found: $file_path");
    }

    $file_content = file_get_contents($file_path);
    if ($file_content === false) {
        throw new Exception("Failed to read file");
    }

    $base64_content = base64_encode($file_content);

    // Determine MIME type for Gemini API
    $mime_type = $file_type;
    
    // Prepare evaluation prompt
    $evaluation_prompt = "You are an expert academic evaluator. Please thoroughly evaluate this student's assignment submission.

Assignment Title: {$assignment_title}
Assignment Description: {$assignment_description}
Student Name: {$student_name}
Student ID: {$student_id}

Provide a comprehensive evaluation in ONLY valid JSON format. Do not include any markdown formatting, preamble, or code blocks. Return only the JSON object:

{
  \"overall_score\": <number between 0-100>,
  \"grade\": \"<letter grade: A/B/C/D/F>\",
  \"strengths\": [\"<strength 1>\", \"<strength 2>\", \"<strength 3>\"],
  \"weaknesses\": [\"<weakness 1>\", \"<weakness 2>\", \"<weakness 3>\"],
  \"errors\": [\"<specific error 1>\", \"<specific error 2>\"],
  \"feedback\": \"<detailed paragraph providing constructive feedback>\",
  \"ai_probability\": <number between 0-100 indicating likelihood of AI-generated content>,
  \"ai_analysis\": \"<explanation of AI detection results>\",
  \"suggestions\": [\"<actionable suggestion 1>\", \"<actionable suggestion 2>\", \"<actionable suggestion 3>\"]
}

Evaluation criteria:
1. Content quality and depth of understanding
2. Technical accuracy and correctness
3. Organization and structure
4. Clarity of explanation
5. Completeness of the solution
6. Evidence of original thinking vs AI-generated content";

    // Prepare Gemini API request
    $api_data = [
        'contents' => [
            [
                'parts' => [
                    [
                        'inline_data' => [
                            'mime_type' => $mime_type,
                            'data' => $base64_content
                        ]
                    ],
                    [
                        'text' => $evaluation_prompt
                    ]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.4,
            'topK' => 32,
            'topP' => 1,
            'maxOutputTokens' => 4096,
        ]
    ];

    // Call Gemini API
    $url = GEMINI_API_URL . '?key=' . GEMINI_API_KEY;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($api_data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 120); // 2 minute timeout

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        throw new Exception("API request failed: $curl_error");
    }

    if ($http_code != 200) {
        $error_response = json_decode($response, true);
        $error_message = "API request failed with code $http_code";
        
        if (isset($error_response['error']['message'])) {
            $error_message = $error_response['error']['message'];
        }
        
        throw new Exception($error_message . " - Response: " . substr($response, 0, 200));
    }

    $api_response = json_decode($response, true);

    // Extract text from Gemini response
    if (!isset($api_response['candidates'][0]['content']['parts'][0]['text'])) {
        throw new Exception("Invalid API response format: " . json_encode($api_response));
    }

    // Parse evaluation JSON
    $evaluation_text = trim($api_response['candidates'][0]['content']['parts'][0]['text']);
    
    // Remove markdown code blocks if present
    $evaluation_text = preg_replace('/^```json\s*/i', '', $evaluation_text);
    $evaluation_text = preg_replace('/\s*```$/', '', $evaluation_text);
    $evaluation_text = trim($evaluation_text);

    $evaluation = json_decode($evaluation_text, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Failed to parse evaluation JSON: " . json_last_error_msg() . " - Response: " . substr($evaluation_text, 0, 500));
    }

    // Validate evaluation structure
    $required_fields = ['overall_score', 'grade', 'strengths', 'weaknesses', 'errors', 'feedback', 'ai_probability', 'ai_analysis', 'suggestions'];
    foreach ($required_fields as $field) {
        if (!isset($evaluation[$field])) {
            throw new Exception("Missing required field in evaluation: $field");
        }
    }

    // Update database with evaluation results
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE submissions SET evaluation_status = 'evaluated', overall_score = ?, grade = ?, ai_probability = ?, feedback = ? WHERE id = ?");

    if (!$stmt) {
        throw new Exception("Database preparation failed: " . $conn->error);
    }

    $feedback_json = json_encode($evaluation, JSON_UNESCAPED_UNICODE);
    $stmt->bind_param("isisi",
        $evaluation['overall_score'],
        $evaluation['grade'],
        $evaluation['ai_probability'],
        $feedback_json,
        $submission_id
    );

    if (!$stmt->execute()) {
        throw new Exception("Failed to update database: " . $stmt->error);
    }

    $stmt->close();
    $conn->close();

    // Return success response
    echo json_encode([
        'success' => true,
        'evaluation' => $evaluation
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // Log error for debugging
    error_log("AI Evaluation Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>