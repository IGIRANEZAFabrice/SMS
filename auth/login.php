<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$identifier = isset($input['identifier']) ? trim($input['identifier']) : '';
$password = isset($input['password']) ? (string)$input['password'] : '';

if ($identifier === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing credentials']);
    exit;
}

$stmt = $conn->prepare(
    'SELECT u.user_id, u.fullname, u.email, u.password, u.role_id, u.is_active, r.role_name
     FROM users u
     LEFT JOIN roles r ON r.role_id = u.role_id
     WHERE (u.email = ? OR u.fullname = ?) LIMIT 1'
);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
    exit;
}

$stmt->bind_param('ss', $identifier, $identifier);
$stmt->execute();
$result = $stmt->get_result();
$user = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    exit;
}

if ((int)$user['is_active'] !== 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Account disabled']);
    exit;
}

$stored = (string)$user['password'];
$valid = false;

if (strlen($stored) >= 10 && ($stored[0] === '$')) {
    $valid = password_verify($password, $stored);
} else {
    $valid = hash('sha256', $password) === $stored || $password === $stored;
}

if (!$valid) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    exit;
}

$_SESSION['user_id'] = (int)$user['user_id'];
$_SESSION['fullname'] = $user['fullname'];
$_SESSION['email'] = $user['email'];
$_SESSION['role_id'] = (int)$user['role_id'];
$_SESSION['role_name'] = $user['role_name'] ?: '';

echo json_encode([
    'success' => true,
    'redirect' => '/SMS/pages/sell.php',
    'user' => [
        'user_id' => (int)$user['user_id'],
        'fullname' => $user['fullname'],
        'email' => $user['email'],
        'role_id' => (int)$user['role_id'],
        'role_name' => $user['role_name'] ?: ''
    ]
]);
?>
