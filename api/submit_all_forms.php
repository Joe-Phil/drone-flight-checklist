<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

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
    
    // Get template info
    $templateDetail = $db->fetchDetailTemplate($templateId);
    $templateData = json_decode($templateDetail, true);
    
    if (!$templateData) {
        throw new Exception('Template not found');
    }
    
    // Get existing submission
    $submission = $db->getSubmissionByTemplate($templateId, $user);
    
    if (!$submission) {
        throw new Exception('No submission found for this template');
    }
    
    // Check if all forms are completed
    $formData = json_decode($submission['formData'], true);
    $templateName = $templateData['templateName'];
    
    $requiredForms = [];
    if ($templateData['assessmentId'] && $templateData['assessmentId'] != '0') {
        $requiredForms['assessment'] = $templateData['assessmentId'];
    }
    if ($templateData['preId'] && $templateData['preId'] != '0') {
        $requiredForms['pre'] = $templateData['preId'];
    }
    if ($templateData['postId'] && $templateData['postId'] != '0') {
        $requiredForms['post'] = $templateData['postId'];
    }
    
    // Check if all required forms are completed
    foreach ($requiredForms as $formType => $formId) {
        if (!isset($formData[$formType]) || empty($formData[$formType])) {
            throw new Exception("Form $formType is not completed");
        }
    }
    
    // Update submission name to indicate completion
    $finalSubmissionName = "$templateName - Completed";
    
    // Update the submission to mark as completed
    $success = $db->updateSubmission($submission['id'], $submission['formData'], $user);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'All forms submitted successfully',
            'submissionId' => $submission['id']
        ]);
    } else {
        throw new Exception('Failed to update submission');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>
