<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Ensure any PHP warnings/notices are returned as JSON (not HTML)
ini_set('display_errors', '0');
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $message]);
    exit;
});
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo json_encode(['error' => 'Fatal error: ' . $e['message']]);
    }
});

include __DIR__ . '/../class/database/master-database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $templateId = $input['templateId'] ?? null;
    $user = $input['user'] ?? null;
    
    if (!$templateId || !$user) {
        throw new Exception('Missing required parameters');
    }
    
    $db = new MasterDatabase();
    
    // Get submission for this template and user
    $submission = $db->getSubmissionByTemplate($templateId, $user);
    
    if ($submission) {
        echo json_encode([
            'success' => true,
            'submission' => [
                'id' => $submission['id'],
                'submissionName' => $submission['submissionName'],
                'templateId' => $submission['templateId'],
                'submittedBy' => $submission['submittedBy'],
                'submittedDate' => $submission['submittedDate'],
                'formData' => $submission['formData']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'submission' => null
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>
