<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $db = new PDO("mysql:host=localhost;dbname=drone_flight_check", "dbaccess", "Ov3r90o0!");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $db->query("SELECT * FROM form");
        $forms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => true, 'data' => $forms]);
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents("php://input"), true);
        if (!$input) throw new Exception("Invalid input JSON");
    
        $id         = $input['id'] ?? null;
        $formName   = $input['formName'] ?? '';
        $formType   = $input['formType'] ?? '';
        $updatedBy  = $input['updatedBy'] ?? '';
        $updatedDate= $input['updatedDate'] ?? date('Y-m-d H:i:s');
        $formData   = json_encode($input['formData'] ?? []);
        $owner      = $input['owner'] ?? null;
        $isPublic = (
            (isset($input['is_public']) && $input['is_public']) || 
            (isset($input['isPublic']) && $input['isPublic'])
        ) ? 1 : 0;
        
    
        $existingId = null;
    
        if ($id !== null) {
            // Cek berdasarkan ID
            $stmt = $db->prepare("SELECT id FROM form WHERE id = ?");
            $stmt->execute([$id]);
            $existingId = $stmt->fetchColumn();
        }
    
        if ($existingId === false || $existingId === null) {
            // Kalau tidak ada ID atau ID tidak ditemukan, cek berdasarkan formName + owner
            $stmt = $db->prepare("SELECT id FROM form WHERE formName = ? AND owner = ?");
            $stmt->execute([$formName, $owner]);
            $existingId = $stmt->fetchColumn();
        }
    
        if ($existingId) {
            // Update
            $stmt = $db->prepare("UPDATE form SET formName=?, formType=?, updatedBy=?, updatedDate=?, formData=?, owner=?, is_public=? WHERE id=?");
            $stmt->execute([$formName, $formType, $updatedBy, $updatedDate, $formData, $owner, $isPublic, $existingId]);
            echo json_encode(['status' => true, 'message' => 'Form updated', 'id' => $existingId]);
            exit();
        }
    
        // Insert jika benar-benar baru
        $stmt = $db->prepare("INSERT INTO form (formName, formType, updatedBy, updatedDate, formData, owner, is_public) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$formName, $formType, $updatedBy, $updatedDate, $formData, $owner, $isPublic]);
        echo json_encode(['status' => true, 'message' => 'Form inserted', 'id' => $db->lastInsertId()]);
        exit();
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => $e->getMessage()]);
}
