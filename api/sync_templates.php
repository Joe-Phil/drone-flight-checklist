<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(['status' => true, 'message' => 'Preflight OK']);
    exit();
}

try {
    $db = new PDO("mysql:host=localhost;dbname=drone_flight_check", "dbaccess", "Ov3r90o0!");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $db->query("SELECT * FROM template");
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => true, 'data' => $templates]);
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents("php://input"), true);
        if (!$input) throw new Exception("Invalid or empty JSON input");
    
        $id = $input['id'] ?? null;
        $templateName = $input['templateName'] ?? '';
        $assessmentId = $input['assessmentId'] ?? 0;
        $preId = $input['preId'] ?? 0;
        $postId = $input['postId'] ?? 0;
        $updatedBy = $input['updatedBy'] ?? '';
        $updatedDate = $input['updatedDate'] ?? date('Y-m-d H:i:s');
        $owner = $input['owner'] ?? null;
        $isPublic = (isset($input['isPublic']) && $input['isPublic']) || (isset($input['is_public']) && $input['is_public']) ? 1 : 0;
    
        // Update by ID
        if ($id !== null) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM template WHERE id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                $update = $db->prepare("UPDATE template SET templateName=?, assessmentId=?, preId=?, postId=?, updatedBy=?, updatedDate=?, owner=?, is_public=? WHERE id=?");
                $update->execute([$templateName, $assessmentId, $preId, $postId, $updatedBy, $updatedDate, $owner, $isPublic, $id]);
                echo json_encode(['status' => true, 'message' => 'Template updated by ID', 'id' => $id]);
                exit();
            }
        }
    
        // Update by templateName + owner
        if ($templateName && $owner) {
            $stmt = $db->prepare("SELECT id FROM template WHERE templateName = ? AND owner = ?");
            $stmt->execute([$templateName, $owner]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                $update = $db->prepare("UPDATE template SET assessmentId=?, preId=?, postId=?, updatedBy=?, updatedDate=?, is_public=? WHERE id=?");
                $update->execute([$assessmentId, $preId, $postId, $updatedBy, $updatedDate, $isPublic, $existing['id']]);
                echo json_encode(['status' => true, 'message' => 'Template updated by name+owner', 'id' => $existing['id']]);
                exit();
            }
        }
    
        // Insert baru
        $insert = $db->prepare("INSERT INTO template (templateName, assessmentId, preId, postId, updatedBy, updatedDate, owner, is_public) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $insert->execute([$templateName, $assessmentId, $preId, $postId, $updatedBy, $updatedDate, $owner, $isPublic]);
        $newId = $db->lastInsertId();
    
        echo json_encode(['status' => true, 'message' => 'Template inserted', 'id' => $newId]);
        exit();
    }
    

    // Method not allowed
    http_response_code(405);
    echo json_encode(['status' => false, 'message' => 'Method not allowed']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => $e->getMessage()]);
}
