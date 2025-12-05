<?php
session_start();

// ------------------------------
// User session
// ------------------------------
$currentUser = $_SESSION['username'] ?? null;
$currentUserRole = $_SESSION['role'] ?? null; // 'student', 'teacher', 'admin'

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
// Database
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
    if (!$currentUser) sendResponse(['success'=>false,'error'=>'Unauthorized'],401);

    $subject = trim($data['subject'] ?? '');
    $message = trim($data['message'] ?? '');
    if ($subject === '' || $message === '') sendResponse(['success'=>false,'error'=>'subject and message required'],400);

    $stmt = $db->prepare("INSERT INTO topics (subject, message, author) VALUES (:sub, :msg, :auth)");
    $stmt->bindParam(':sub', sanitizeInput($subject));
    $stmt->bindParam(':msg', sanitizeInput($message));
    $stmt->bindParam(':auth', sanitizeInput($currentUser));

    if ($stmt->execute()) {
        $id = $db->lastInsertId();
        $stmt2 = $db->prepare("SELECT id, subject, message, author, DATE_FORMAT(created_at,'%Y-%m-%d %H:%i:%s') as created_at FROM topics WHERE id=:id");
        $stmt2->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt2->execute();
        $topic = $stmt2->fetch(PDO::FETCH_ASSOC);
        sendResponse(['success'=>true,'data'=>$topic],201);
    } else {
        sendResponse(['success'=>false,'error'=>'Failed to create topic'],500);
    }
}

function updateTopic($db, $data, $currentUser, $currentUserRole) {
    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) sendResponse(['success'=>false,'error'=>'Topic ID required'],400);
    if (!$currentUser) sendResponse(['success'=>false,'error'=>'Unauthorized'],401);

    // Fetch author
    $stmt = $db->prepare("SELECT author FROM topics WHERE id=:id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $topic = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$topic) sendResponse(['success'=>false,'error'=>'Topic not found'],404);

    $author = $topic['author'];
    $isTeacher = ($currentUserRole === 'teacher' || $currentUserRole === 'admin');
    if (($currentUser !== $author) && !$isTeacher) sendResponse(['success'=>false,'error'=>'Permission denied'],403);

    $fields = [];
    $params = [':id'=>$id];

    if(isset($data['subject'])){
        $fields[] = 'subject=:sub';
        $params[':sub'] = sanitizeInput($data['subject']);
    }
    if(isset($data['message'])){
        $fields[] = 'message=:msg';
        $params[':msg'] = sanitizeInput($data['message']);
    }
    if(empty($fields)) sendResponse(['success'=>false,'error'=>'Nothing to update'],400);

    $sql = "UPDATE topics SET ".implode(', ',$fields)." WHERE id=:id";
    $stmt = $db->prepare($sql);
    foreach($params as $k=>$v) $stmt->bindValue($k,$v);
    if($stmt->execute()) sendResponse(['success'=>true,'message'=>'Topic updated']);
    else sendResponse(['success'=>false,'error'=>'Failed to update topic'],500);
}

function deleteTopic($db, $id, $currentUser, $currentUserRole) {
    if (!$id) sendResponse(['success'=>false,'error'=>'Topic ID required'],400);
    if (!$currentUser) sendResponse(['success'=>false,'error'=>'Unauthorized'],401);

    $stmt = $db->prepare("SELECT author FROM topics WHERE id=:id");
    $stmt->bindParam(':id',$id,PDO::PARAM_INT);
    $stmt->execute();
    $topic = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$topic) sendResponse(['success'=>false,'error'=>'Topic not found'],404);

    $author = $topic['author'];
    $isTeacher = ($currentUserRole === 'teacher' || $currentUserRole === 'admin');
    if (($currentUser !== $author) && !$isTeacher) sendResponse(['success'=>false,'error'=>'Permission denied'],403);

    $stmt = $db->prepare("DELETE FROM topics WHERE id=:id");
    $stmt->bindParam(':id',$id,PDO::PARAM_INT);
    if($stmt->execute()) sendResponse(['success'=>true,'message'=>'Topic deleted']);
    else sendResponse(['success'=>false,'error'=>'Failed to delete topic'],500);
}

// ------------------------------
// Router
// ------------------------------
$method = $_SERVER['REQUEST_METHOD'];
$payload = getJsonPayload();
$id = isset($_GET['id']) ? (int)$_GET['id'] : ($payload['id'] ?? 0);

try {
    switch($method){
        case 'GET': getAllTopics($db); break;
        case 'POST': createTopic($db,$payload,$currentUser); break;
        case 'PUT': updateTopic($db,$payload,$currentUser,$currentUserRole); break;
        case 'DELETE': deleteTopic($db,$id,$currentUser,$currentUserRole); break;
        default: sendResponse(['success'=>false,'error'=>'Method not allowed'],405);
    }
} catch(PDOException $e){
    sendResponse(['success'=>false,'error'=>$e->getMessage()],500);
}
?>
