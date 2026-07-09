<?php
header('Content-Type: application/json; charset=utf-8');

date_default_timezone_set('Asia/Jakarta');

// Ganti ini sesuai cara koneksi yang dipakai file API lain (sync_forms.php/sync_templates.php)
// Jika API lain sudah pakai file config/DB helper sendiri, gunakan require_once yang sama di sini.
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = 'root';
$DB_NAME = 'drone_flight_check';

function db_connect() {
  global $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME;
  $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
  if ($mysqli->connect_errno) {
    http_response_code(500);
    echo json_encode(['status' => false, 'message' => 'DB connect error: ' . $mysqli->connect_error]);
    exit;
  }
  $mysqli->set_charset('utf8mb4');
  return $mysqli;
}

function read_json_body() {
  $raw = file_get_contents('php://input');
  if (!$raw) return null;
  $data = json_decode($raw, true);
  return $data;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
  if ($method === 'GET') {
    // Optional filter: ?user=<username>&limit=...
    $user = isset($_GET['user']) ? $_GET['user'] : null;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;

    $db = db_connect();
    if ($user) {
      $stmt = $db->prepare("SELECT id, submissionName, templateId, submittedBy, submittedDate, formData FROM submission WHERE submittedBy = ? ORDER BY id DESC LIMIT ?");
      $stmt->bind_param('si', $user, $limit);
    } else {
      $stmt = $db->prepare("SELECT id, submissionName, templateId, submittedBy, submittedDate, formData FROM submission ORDER BY id DESC LIMIT ?");
      $stmt->bind_param('i', $limit);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    $rows = [];
    while ($row = $res->fetch_assoc()) {
      // Pastikan formData dalam bentuk struktur JSON (bukan string double-encoded)
      $decoded = json_decode($row['formData'], true);
      $row['formData'] = $decoded !== null ? $decoded : $row['formData'];
      $rows[] = $row;
    }
    $stmt->close();
    $db->close();

    echo json_encode(['status' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
    exit;
  }

  if ($method === 'POST') {
    $data = read_json_body();
    if (!$data) {
      http_response_code(400);
      echo json_encode(['status' => false, 'message' => 'Invalid JSON body']);
      exit;
    }

    // Validasi field minimal
    $submissionName = isset($data['submissionName']) ? trim($data['submissionName']) : '';
    $templateId     = isset($data['templateId']) ? intval($data['templateId']) : 0;
    $submittedBy    = isset($data['submittedBy']) ? trim($data['submittedBy']) : '';
    $submittedDate  = isset($data['submittedDate']) ? trim($data['submittedDate']) : date('c');
    $formData       = isset($data['formData']) ? $data['formData'] : [];

    if ($submissionName === '' || $templateId <= 0 || $submittedBy === '') {
      http_response_code(400);
      echo json_encode(['status' => false, 'message' => 'submissionName/templateId/submittedBy is required']);
      exit;
    }

    $db = db_connect();
    $jsonFormData = json_encode($formData, JSON_UNESCAPED_UNICODE);

    $stmt = $db->prepare("INSERT INTO submission (submissionName, templateId, submittedBy, submittedDate, formData) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('sisss', $submissionName, $templateId, $submittedBy, $submittedDate, $jsonFormData);
    $ok = $stmt->execute();
    $newId = $stmt->insert_id;
    $err   = $stmt->error;
    $stmt->close();
    $db->close();

    if (!$ok) {
      http_response_code(500);
      echo json_encode(['status' => false, 'message' => 'Insert failed: ' . $err]);
      exit;
    }

    echo json_encode(['status' => 'success', 'id' => $newId], JSON_UNESCAPED_UNICODE);
    exit;
  }

  if ($method === 'PUT') {
    // Update submission by id: ?id=123
    if (!isset($_GET['id'])) {
      http_response_code(400);
      echo json_encode(['status' => false, 'message' => 'Missing id']);
      exit;
    }
    $id = intval($_GET['id']);
    $data = read_json_body();
    if (!$data) {
      http_response_code(400);
      echo json_encode(['status' => false, 'message' => 'Invalid JSON body']);
      exit;
    }

    $submissionName = isset($data['submissionName']) ? trim($data['submissionName']) : null;
    $templateId     = isset($data['templateId']) ? intval($data['templateId']) : null;
    $submittedBy    = isset($data['submittedBy']) ? trim($data['submittedBy']) : null;
    $submittedDate  = isset($data['submittedDate']) ? trim($data['submittedDate']) : null;
    $formData       = array_key_exists('formData', $data) ? $data['formData'] : null;

    $fields = [];
    $params = [];
    $types  = '';

    if ($submissionName !== null) { $fields[] = 'submissionName = ?'; $params[] = $submissionName; $types .= 's'; }
    if ($templateId !== null)     { $fields[] = 'templateId = ?';     $params[] = $templateId;     $types .= 'i'; }
    if ($submittedBy !== null)    { $fields[] = 'submittedBy = ?';    $params[] = $submittedBy;    $types .= 's'; }
    if ($submittedDate !== null)  { $fields[] = 'submittedDate = ?';  $params[] = $submittedDate;  $types .= 's'; }
    if ($formData !== null)       { $fields[] = 'formData = ?';       $params[] = json_encode($formData, JSON_UNESCAPED_UNICODE); $types .= 's'; }

    if (empty($fields)) {
      echo json_encode(['status' => false, 'message' => 'No fields to update']);
      exit;
    }

    $setClause = implode(', ', $fields);
    $db = db_connect();
    $sql = "UPDATE submission SET $setClause WHERE id = ?";
    $stmt = $db->prepare($sql);
    $types .= 'i';
    $params[] = $id;
    $stmt->bind_param($types, ...$params);
    $ok = $stmt->execute();
    $err = $stmt->error;
    $stmt->close();
    $db->close();

    if (!$ok) {
      http_response_code(500);
      echo json_encode(['status' => false, 'message' => 'Update failed: ' . $err]);
      exit;
    }

    echo json_encode(['status' => 'success']);
    exit;
  }

  http_response_code(405);
  echo json_encode(['status' => false, 'message' => 'Method Not Allowed']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['status' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}