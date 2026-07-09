<?php
/*
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../class/database/connect.php';

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('Invalid JSON input');
        }
        
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            throw new Exception('Username and password are required');
        }
        
        // Connect to database
        $database = new Connect();
        $conn = $database->getConnection();
        
        // Check if user exists
        $stmt = $conn->prepare("SELECT username, password FROM user WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Check password using SHA-512
            if (hash('sha512', $password) === $user['password']) {
                // Login successful
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Login successful',
                    'user' => [
                        'username' => $user['username']
                    ]
                ]);
            } else {
                // Invalid password
                http_response_code(401);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid username or password'
                ]);
            }
        } else {
            // User not found
            http_response_code(401);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid username or password'
            ]);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
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
*/

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../class/database/connect.php';

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            throw new Exception('Invalid JSON input');
        }

        $identifier = $input['username'] ?? '';
        $password = $input['password'] ?? '';

        if (empty($identifier) || empty($password)) {
            throw new Exception('Username/Email and password are required');
        }

        // Connect to database
        $database = new Connect();
        $conn = $database->getConnection();

        // Check if user exists by username OR email
        $stmt = $conn->prepare("SELECT username, email, password FROM user WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Check password using SHA-512
            if (hash('sha512', $password) === $user['password']) {
                // Login successful
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Login successful',
                    'username' => $user['username'],
                    'email' => $user['email']
                ]);
            } else {
                // Invalid password
                http_response_code(401);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid username/email or password'
                ]);
            }
        } else {
            // User not found
            http_response_code(401);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid username/email or password'
            ]);
        }

    } catch (Exception $e) {
        http_response_code(500);
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