<?php
session_start();

// ------------------------------
// Simulated User Session (replace with real auth in production)
// ------------------------------
$currentUser = $_SESSION['username'] ?? 'TestUser'; // مؤقت لاختبار
$currentUserRole = $_SESSION['role'] ?? 'student'; // مؤقت

// ------------------------------
// Headers
// ------------------------------
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ------------------------------
// Database Connection
// ------------------------------
require_once __DIR__ . '/Database.php';
$database = new Database();
$db = $database->getConnection();

// ------------------------------
// Helpers
// ------------------------------
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

function sanitizeInput($data) {
    if (!is_string($data)) $data = strval($data);
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function getJsonPayload() {
    $body = file_get_contents('php://input');
    if ($body) {
        $decoded = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) return $decoded;
        parse_str($body, $parsed);
        if (!empty($parsed)) return $parsed;
    }
    if (!empty($_POST)) return $_POST;
    return [];
}

// ------------------------------
// CRUD Functions
// ------------------------------
function getAllTopics($db) {
    $stmt = $db->query("SELECT id, subject, message, author, DATE_FORMAT(created_at,'%Y-%m-%d %H:%i:%s') as created_at FROM topics ORDER BY created_at DESC");
    $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse(['success'=>true,'data'=>$topics]);
}

function createTopic($db, $data, $currentUser) {
    $author = $currentUser ?? ($data['author'] ?? 'TestUser');
    $subject = trim($data['subject'] ?? '');
    $message = trim($data['message'] ?? '');

    if ($subject === '' || $message === '') sendResponse(['success'=>false,'error'=>'subject and message are required'],400);

    $subject_s = sanitizeInput($subject);
    $message_s = sanitizeInput($message);
    $author_s = sanitizeInput($author);

    $stmt = $db->prepare("INSERT INTO topics (subject, message, author) VALUES (:sub, :msg, :auth)");
    $stmt->bindParam(':sub',$subject_s);
    $stmt->bindParam(':msg',$message_s);
    $stmt->bindParam(':auth',$author_s);

    if ($stmt->execute()) {
        $newId = (int)$db->lastInsertId();
        $stmt2 = $db->prepare("SELECT id, subject, message, author, DATE_FORMAT(created_at,'%Y-%m-%d %H:%i:%s') as created_at FROM topics WHERE id = :id LIMIT 1");
        $stmt2->bindParam(':id',$newId,PDO::PARAM_INT);
        $stmt2->execute();
        $topic = $stmt2->fetch(PDO::FETCH_ASSOC);
        sendResponse(['success'=>true,'data'=>$topic],201);
    } else {
        sendResponse(['success'=>false,'error'=>'Failed to create topic'],500);
    }
}

// ------------------------------
// Router
// ------------------------------
$method = $_SERVER['REQUEST_METHOD'];
$payload = getJsonPayload();

try {
    switch($method) {
        case 'GET':
            getAllTopics($db);
            break;
        case 'POST':
            createTopic($db, $payload, $currentUser);
            break;
        default:
            sendResponse(['success'=>false,'error'=>'Method not allowed'],405);
    }
} catch(PDOException $e){
    sendResponse(['success'=>false,'error'=>$e->getMessage()],500);
}
?>
