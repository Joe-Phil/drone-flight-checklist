<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../class/database/connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('Invalid JSON input');
        }
        
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';
        $email = $input['email'] ?? '';
        
        if (empty($username) || empty($password) || empty($email)) {
            throw new Exception('Username, password, and email are required');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
        
        // Initialize database connection
        $database = new Connect();
        $conn = $database->getConnection();
        
        if (!$conn) {
            throw new Exception('Database connection failed');
        }
        
        // Check if username already exists
        $checkUser = "SELECT username FROM user WHERE username = ?";
        $stmt = $conn->prepare($checkUser);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            http_response_code(409);
            echo json_encode([
                'status' => 'error',
                'message' => 'Username already exists'
            ]);
            exit();
        }

        // Check if email already exists
        $checkEmail = "SELECT email FROM user WHERE email = ?";
        $stmt = $conn->prepare($checkEmail);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            http_response_code(409);
            echo json_encode([
                'status' => 'error',
                'message' => 'Email already exists'
            ]);
            exit();
        }
        
        // Hash password using SHA512 to match existing password format
        $hashedPassword = hash('sha512', $password);
        
        // Insert new user
        $insertQuery = "INSERT INTO user (username, password, email) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        
        if (!$stmt) {
            throw new Exception('Failed to prepare statement');
        }
        
        $stmt->bind_param("sss", $username, $hashedPassword, $email);
        
        if ($stmt->execute()) {
            echo json_encode([
                'status' => 'success',
                'message' => 'User registered successfully',
                'user' => [
                    'username' => $username,
                    'email' => $email
                ]
            ]);
        } else {
            throw new Exception('Failed to register user');
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed'
    ]);
}
?>