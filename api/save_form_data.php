<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
// Force JSON-only output; convert PHP warnings/notices to JSON
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
    // support both JSON payloads and multipart/form-data (for file uploads)
    $isMultipart = isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/') === 0;

    if ($isMultipart) {
        $templateId = $_POST['templateId'] ?? null;
        $formId = $_POST['formId'] ?? null;
        $formType = $_POST['formType'] ?? null;
        $formData = isset($_POST['formData']) ? json_decode($_POST['formData'], true) : [];
        $submissionTitle = $_POST['submissionTitle'] ?? '';
        $user = $_POST['user'] ?? null;
        $action = $_POST['action'] ?? 'save';
    } else {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            throw new Exception('Invalid JSON input');
        }

        $templateId = $input['templateId'] ?? null;
        $formId = $input['formId'] ?? null;
        $formType = $input['formType'] ?? null;
        $formData = $input['formData'] ?? null;
        $submissionTitle = $input['submissionTitle'] ?? '';
        $user = $input['user'] ?? null;
        $action = $input['action'] ?? 'save'; // 'save' or 'submit'
    }

    if (!is_array($formData)) {
        $formData = [];
    }

    // process file uploads if any
    $uploads = [];
    if ($isMultipart && !empty($_FILES)) {
        foreach ($_FILES as $field => $info) {
            $cleanField = preg_replace('/\[\]$/', '', $field);
            if (is_array($info['name'])) {
                for ($i = 0; $i < count($info['name']); $i++) {
                    if ($info['error'][$i] === UPLOAD_ERR_OK) {
                        $ext = pathinfo($info['name'][$i], PATHINFO_EXTENSION);
                        $destDir = __DIR__ . '/../uploads';
                        if (!is_dir($destDir)) mkdir($destDir, 0755, true);
                        $dest = $destDir . '/' . uniqid('photo_') . "." . $ext;
                        move_uploaded_file($info['tmp_name'][$i], $dest);
                        // store simple relative path under uploads directory (no leading slash)
                        $uploads[$cleanField][] = 'uploads/' . basename($dest);
                    }
                }
            } else {
                if ($info['error'] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($info['name'], PATHINFO_EXTENSION);
                    $destDir = __DIR__ . '/../uploads';
                    if (!is_dir($destDir)) mkdir($destDir, 0755, true);
                    $dest = $destDir . '/' . uniqid('photo_') . "." . $ext;
                    move_uploaded_file($info['tmp_name'], $dest);
                    $uploads[$cleanField] = 'uploads/' . basename($dest);
                }
            }
        }

        // merge uploaded paths into formData
        foreach ($uploads as $field => $path) {
            if (isset($formData[$field])) {
                if (is_array($formData[$field])) {
                    if (is_array($path)) {
                        $formData[$field] = array_merge($formData[$field], $path);
                    } else {
                        $formData[$field][] = $path;
                    }
                } else {
                    // convert existing single value to array
                    $formData[$field] = is_array($path) ? array_merge([$formData[$field]], $path) : [$formData[$field], $path];
                }
            } else {
                $formData[$field] = $path;
            }
        }
    }

    if (!$templateId || !$formId || !$formType || !$user) {
        throw new Exception('Missing required parameters');
    }

    $db = new MasterDatabase();

    // Get template info for submission name
    $templateDetail = $db->fetchDetailTemplate($templateId);
    $templateData = json_decode($templateDetail, true);
    $templateName = $templateData['templateName'] ?? 'Unknown Template';

    // Get form info
    $formDetail = $db->fetchDetailForm($formId);
    $formDataObj = json_decode($formDetail, true);
    if (!is_array($formDataObj)) {
        throw new Exception('Form not found');
    }
    $formName = $formDataObj['formName'] ?? 'Unknown Form';

    // Create submission name (use provided title if available)
    $submissionName = trim($submissionTitle) !== '' ? $submissionTitle : ("$templateName - $formName");

    // Check if submission already exists for this template and user
    $existingSubmission = $db->getSubmissionByTemplate($templateId, $user);

    if ($existingSubmission) {
        // Update existing submission
        $currentFormData = is_string($existingSubmission['formData'])
            ? (json_decode($existingSubmission['formData'], true) ?: [])
            : (is_array($existingSubmission['formData']) ? $existingSubmission['formData'] : []);
        $currentFormData[$formType] = $formData;
        $updatedFormData = json_encode($currentFormData);

        $success = $db->updateSubmission($existingSubmission['id'], $updatedFormData, $user);

        if ($success) {
            // record any new uploads in helper table
            if (!empty($uploads)) {
                foreach ($uploads as $field => $paths) {
                    if (is_array($paths)) {
                        foreach ($paths as $p) {
                            $db->saveSubmissionFile($existingSubmission['id'], $formType, $field, $p);
                        }
                    } else {
                        $db->saveSubmissionFile($existingSubmission['id'], $formType, $field, $paths);
                    }
                }
            }
            echo json_encode([
                'success' => true,
                'message' => 'Form data saved successfully',
                'submissionId' => $existingSubmission['id'],
                'action' => 'updated'
            ]);
        } else {
            throw new Exception('Failed to update submission');
        }
    } else {
        // Create new submission
        $newFormData = json_encode([$formType => $formData], JSON_UNESCAPED_UNICODE);
        $submissionId = $db->saveSubmission($templateId, $submissionName, $newFormData, $user);

        if ($submissionId) {
            // record any uploads
            if (!empty($uploads)) {
                foreach ($uploads as $field => $paths) {
                    if (is_array($paths)) {
                        foreach ($paths as $p) {
                            $db->saveSubmissionFile($submissionId, $formType, $field, $p);
                        }
                    } else {
                        $db->saveSubmissionFile($submissionId, $formType, $field, $paths);
                    }
                }
            }
            echo json_encode([
                'success' => true,
                'message' => 'Form data saved successfully',
                'submissionId' => $submissionId,
                'action' => 'created'
            ]);
        } else {
            // Provide more context in logs (not exposed to client for security)
            error_log('Failed to create submission for templateId=' . $templateId . ', user=' . $user . ' dbErr=' . $db->getDbError());
            throw new Exception('Failed to create submission');
        }
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>
