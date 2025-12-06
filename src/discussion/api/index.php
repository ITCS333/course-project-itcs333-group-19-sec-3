<?php
session_start();
require 'Database.php';

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
// Database connection
// ------------------------------
$database = new Database();
$db = $database->getConnection();

// ------------------------------
// User session
// ------------------------------
$currentUser = $_SESSION['username'] ?? null;
$currentUserRole = $_SESSION['role'] ?? null; // 'student', 'teacher', 'admin'

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
// TOPICS CRUD
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
        sendResponse(['success'=>true,'data'=>['id'=>$id,'subject'=>$subject,'message'=>$message,'author'=>$currentUser]],201);
    } else {
        sendResponse(['success'=>false,'error'=>'Failed to create topic'],500);
    }
}

function updateTopic($db, $data, $currentUser, $currentUserRole) {
    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) sendResponse(['success'=>false,'error'=>'Topic ID required'],400);
    if (!$currentUser) sendResponse(['success'=>false,'error'=>'Unauthorized'],401);

    // check ownership
    $stmt = $db->prepare("SELECT author FROM topics WHERE id=:id");
    $stmt->bindParam(':id',$id,PDO::PARAM_INT);
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
    if ($id <= 0) sendResponse(['success'=>false,'error'=>'Topic ID required'],400);
    if (!$currentUser) sendResponse(['success'=>false,'error'=>'Unauthorized'],401);

    // check ownership
    $stmt = $db->prepare("SELECT author FROM topics WHERE id=:id");
    $stmt->bindParam(':id',$id,PDO::PARAM_INT);
    $stmt->execute();
    $topic = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$topic) sendResponse(['success'=>false,'error'=>'Topic not found'],404);

    $author = $topic['author'];
    $isTeacher = ($currentUserRole === 'teacher' || $currentUserRole === 'admin');
    if (($currentUser !== $author) && !$isTeacher) sendResponse(['success'=>false,'error'=>'Permission denied'],403);

    // delete replies first
    $stmt = $db->prepare("DELETE FROM replies WHERE topic_id=:id");
    $stmt->bindParam(':id',$id,PDO::PARAM_INT);
    $stmt->execute();

    $stmt = $db->prepare("DELETE FROM topics WHERE id=:id");
    $stmt->bindParam(':id',$id,PDO::PARAM_INT);
    if($stmt->execute()) sendResponse(['success'=>true,'message'=>'Topic deleted']);
    else sendResponse(['success'=>false,'error'=>'Failed to delete topic'],500);
}

// ------------------------------
// REPLIES CRUD
// ------------------------------
function getReplies($db, $topic_id) {
    if (!$topic_id) sendResponse(['success'=>false,'error'=>'topic_id required'],400);
    $stmt = $db->prepare("SELECT id, topic_id, text, author, DATE_FORMAT(created_at,'%Y-%m-%d %H:%i:%s') as created_at FROM replies WHERE topic_id=:tid ORDER BY created_at ASC");
    $stmt->bindParam(':tid',$topic_id,PDO::PARAM_INT);
    $stmt->execute();
    $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse(['success'=>true,'data'=>$replies]);
}

function createReply($db, $data, $currentUser) {
    if (!$currentUser) sendResponse(['success'=>false,'error'=>'Unauthorized'],401);

    $topic_id = (int)($data['topic_id'] ?? 0);
    $text = trim($data['text'] ?? '');
    if ($topic_id <=0 || $text==='') sendResponse(['success'=>false,'error'=>'topic_id and text required'],400);

    // verify topic exists
    $stmt = $db->prepare("SELECT id FROM topics WHERE id=:tid");
    $stmt->bindParam(':tid',$topic_id,PDO::PARAM_INT);
    $stmt->execute();
    if (!$stmt->fetch()) sendResponse(['success'=>false,'error'=>'Topic does not exist'],404);

    $stmt = $db->prepare("INSERT INTO replies (topic_id, text, author) VALUES (:tid, :txt, :auth)");
    $stmt->bindParam(':tid',$topic_id,PDO::PARAM_INT);
    $stmt->bindParam(':txt',sanitizeInput($text));
    $stmt->bindParam(':auth',$currentUser);

    if($stmt->execute()) sendResponse(['success'=>true,'data'=>['id'=>$db->lastInsertId(),'topic_id'=>$topic_id,'text'=>$text,'author'=>$currentUser]],201);
    else sendResponse(['success'=>false,'error'=>'Failed to create reply'],500);
}

function deleteReply($db, $id, $currentUser, $currentUserRole){
    if ($id <= 0) sendResponse(['success'=>false,'error'=>'Reply ID required'],400);
    if (!$currentUser) sendResponse(['success'=>false,'error'=>'Unauthorized'],401);

    $stmt = $db->prepare("SELECT author FROM replies WHERE id=:rid");
    $stmt->bindParam(':rid',$id,PDO::PARAM_INT);
    $stmt->execute();
    $reply = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$reply) sendResponse(['success'=>false,'error'=>'Reply not found'],404);

    $author = $reply['author'];
    $isTeacher = ($currentUserRole==='teacher' || $currentUserRole==='admin');
    if (($currentUser!==$author)&&!$isTeacher) sendResponse(['success'=>false,'error'=>'Permission denied'],403);

    $stmt = $db->prepare("DELETE FROM replies WHERE id=:rid");
    $stmt->bindParam(':rid',$id,PDO::PARAM_INT);
    if($stmt->execute()) sendResponse(['success'=>true,'message'=>'Reply deleted']);
    else sendResponse(['success'=>false,'error'=>'Failed to delete reply'],500);
}

// ------------------------------
// Router
// ------------------------------
$method = $_SERVER['REQUEST_METHOD'];
$resource = $_GET['resource'] ?? null;
$id = $_GET['id'] ?? null;
$payload = getJsonPayload();

switch($resource){
    case 'topics':
        switch($method){
            case 'GET': getAllTopics($db); break;
            case 'POST': createTopic($db,$payload,$currentUser); break;
            case 'PUT': updateTopic($db,$payload,$currentUser,$currentUserRole); break;
            case 'DELETE': deleteTopic($db,$id,$currentUser,$currentUserRole); break;
            default: sendResponse(['success'=>false,'error'=>'Method not allowed'],405);
        }
        break;
    case 'replies':
        switch($method){
            case 'GET': getReplies($db,$id); break;
            case 'POST': createReply($db,$payload,$currentUser); break;
            case 'DELETE': deleteReply($db,$id,$currentUser,$currentUserRole); break;
            default: sendResponse(['success'=>false,'error'=>'Method not allowed'],405);
        }
        break;
    default:
        sendResponse(['success'=>false,'error'=>'Invalid resource'],400);
}
?>
