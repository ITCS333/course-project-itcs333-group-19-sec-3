<?php
session_start();

// ==============================
// Simulated User Session
// ==============================
$currentUser = $_SESSION['username'] ?? null;
$currentUserRole = $_SESSION['role'] ?? 'student'; // 'student' or 'teacher'

// ==============================
// Headers for JSON response and CORS
// ==============================
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ==============================
// Include Database Connection
// ==============================
require_once 'Database.php'; // Assume this returns PDO instance
$database = new Database();
$db = $database->getConnection();

// ==============================
// Get HTTP method and input
// ==============================
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$resource = $_GET['resource'] ?? null;
$id = $_GET['id'] ?? null;

// ==============================
// Helper Functions
// ==============================
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

function sanitizeInput($data) {
    if (!is_string($data)) $data = strval($data);
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function isValidResource($resource) {
    $allowed = ['topics', 'replies'];
    return in_array($resource, $allowed);
}

// ==============================
// ==============================
// TOPICS FUNCTIONS
// ==============================

function getAllTopics($db) {
    $search = $_GET['search'] ?? '';
    $sort = $_GET['sort'] ?? 'created_at';
    $order = strtolower($_GET['order'] ?? 'desc');

    $allowedSort = ['subject', 'author', 'created_at'];
    if (!in_array($sort, $allowedSort)) $sort = 'created_at';
    if (!in_array($order, ['asc','desc'])) $order = 'desc';

    $params = [];
    $sql = "SELECT topic_id, subject, message, author, DATE_FORMAT(created_at,'%Y-%m-%d %H:%i:%s') as created_at FROM topics";

    if ($search) {
        $sql .= " WHERE subject LIKE :s OR message LIKE :s OR author LIKE :s";
        $params[':s'] = "%$search%";
    }

    $sql .= " ORDER BY $sort $order";
    $stmt = $db->prepare($sql);

    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }

    $stmt->execute();
    $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse(['success'=>true,'data'=>$topics]);
}

function getTopicById($db, $topicId) {
    if (!$topicId) sendResponse(['error'=>'Topic ID required'], 400);

    $stmt = $db->prepare("SELECT topic_id, subject, message, author, DATE_FORMAT(created_at,'%Y-%m-%d %H:%i:%s') as created_at FROM topics WHERE topic_id = :tid");
    $stmt->bindParam(':tid', $topicId);
    $stmt->execute();
    $topic = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($topic) sendResponse(['success'=>true,'data'=>$topic]);
    sendResponse(['error'=>'Topic not found'],404);
}

function createTopic($db, $data, $currentUser) {
    if (!$currentUser) sendResponse(['error'=>'Unauthorized'],401);

    $required = ['subject','message'];
    foreach($required as $f){
        if(empty($data[$f])) sendResponse(['error'=>"$f is required"],400);
    }

    $topic_id = 'topic_'.time().rand(1000,9999);
    $subject = sanitizeInput($data['subject']);
    $message = sanitizeInput($data['message']);
    $author = $currentUser;

    $stmt = $db->prepare("INSERT INTO topics (topic_id, subject, message, author) VALUES (:tid,:sub,:msg,:auth)");
    $stmt->bindParam(':tid',$topic_id);
    $stmt->bindParam(':sub',$subject);
    $stmt->bindParam(':msg',$message);
    $stmt->bindParam(':auth',$author);

    if($stmt->execute()){
        sendResponse(['success'=>true,'topic_id'=>$topic_id],201);
    } else {
        sendResponse(['error'=>'Failed to create topic'],500);
    }
}

function updateTopic($db, $data, $currentUser, $currentUserRole) {
    if (!$currentUser) sendResponse(['error'=>'Unauthorized'],401);
    if (empty($data['topic_id'])) sendResponse(['error'=>'topic_id required'],400);

    // Check topic exists
    $stmt = $db->prepare("SELECT author FROM topics WHERE topic_id = :tid");
    $stmt->bindParam(':tid', $data['topic_id']);
    $stmt->execute();
    $topic = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$topic) sendResponse(['error'=>'Topic not found'],404);

    // Ownership check
    if($topic['author'] !== $currentUser && $currentUserRole !== 'teacher'){
        sendResponse(['error'=>'Permission denied'],403);
    }

    $fields = [];
    $params = [':tid'=>$data['topic_id']];
    if(!empty($data['subject'])) {
        $fields[] = 'subject=:sub';
        $params[':sub'] = sanitizeInput($data['subject']);
    }
    if(!empty($data['message'])) {
        $fields[] = 'message=:msg';
        $params[':msg'] = sanitizeInput($data['message']);
    }

    if(empty($fields)) sendResponse(['error'=>'Nothing to update'],400);

    $sql = "UPDATE topics SET ".implode(', ',$fields)." WHERE topic_id=:tid";
    $stmt = $db->prepare($sql);
    foreach($params as $k=>$v) $stmt->bindValue($k,$v);

    if($stmt->execute()){
        sendResponse(['success'=>true,'message'=>'Topic updated']);
    } else {
        sendResponse(['error'=>'Failed to update topic'],500);
    }
}

function deleteTopic($db, $topicId, $currentUser, $currentUserRole) {
    if (!$currentUser) sendResponse(['error'=>'Unauthorized'],401);
    if (!$topicId) sendResponse(['error'=>'Topic ID required'],400);

    // Check topic exists
    $stmt = $db->prepare("SELECT author FROM topics WHERE topic_id = :tid");
    $stmt->bindParam(':tid',$topicId);
    $stmt->execute();
    $topic = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$topic) sendResponse(['error'=>'Topic not found'],404);

    // Ownership check
    if($topic['author'] !== $currentUser && $currentUserRole !== 'teacher'){
        sendResponse(['error'=>'Permission denied'],403);
    }

    // Delete replies first
    $stmt = $db->prepare("DELETE FROM replies WHERE topic_id=:tid");
    $stmt->bindParam(':tid',$topicId);
    $stmt->execute();

    // Delete topic
    $stmt = $db->prepare("DELETE FROM topics WHERE topic_id=:tid");
    $stmt->bindParam(':tid',$topicId);
    if($stmt->execute()){
        sendResponse(['success'=>true,'message'=>'Topic deleted']);
    } else {
        sendResponse(['error'=>'Failed to delete topic'],500);
    }
}

// ==============================
// REPLIES FUNCTIONS
// ==============================

function getRepliesByTopicId($db, $topicId) {
    if(!$topicId) sendResponse(['error'=>'Topic ID required'],400);
    $stmt = $db->prepare("SELECT reply_id, topic_id, text, author, DATE_FORMAT(created_at,'%Y-%m-%d %H:%i:%s') as created_at FROM replies WHERE topic_id=:tid ORDER BY created_at ASC");
    $stmt->bindParam(':tid',$topicId);
    $stmt->execute();
    $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse(['success'=>true,'data'=>$replies]);
}

function createReply($db, $data, $currentUser) {
    if (!$currentUser) sendResponse(['error'=>'Unauthorized'],401);

    $required = ['topic_id','text'];
    foreach($required as $f){
        if(empty($data[$f])) sendResponse(['error'=>"$f is required"],400);
    }

    $topicId = $data['topic_id'];
    $text = sanitizeInput($data['text']);
    $author = $currentUser;
    $reply_id = 'reply_'.time().rand(1000,9999);

    // Check topic exists
    $stmt = $db->prepare("SELECT topic_id FROM topics WHERE topic_id=:tid");
    $stmt->bindParam(':tid',$topicId);
    $stmt->execute();
    if(!$stmt->fetch(PDO::FETCH_ASSOC)) sendResponse(['error'=>'Topic not found'],404);

    $stmt = $db->prepare("INSERT INTO replies (reply_id, topic_id, text, author) VALUES (:rid,:tid,:txt,:auth)");
    $stmt->bindParam(':rid',$reply_id);
    $stmt->bindParam(':tid',$topicId);
    $stmt->bindParam(':txt',$text);
    $stmt->bindParam(':auth',$author);

    if($stmt->execute()){
        sendResponse(['success'=>true,'reply_id'=>$reply_id],201);
    } else {
        sendResponse(['error'=>'Failed to create reply'],500);
    }
}

function deleteReply($db, $replyId, $currentUser, $currentUserRole) {
    if (!$currentUser) sendResponse(['error'=>'Unauthorized'],401);
    if (!$replyId) sendResponse(['error'=>'Reply ID required'],400);

    $stmt = $db->prepare("SELECT author FROM replies WHERE reply_id=:rid");
    $stmt->bindParam(':rid',$replyId);
    $stmt->execute();
    $reply = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$reply) sendResponse(['error'=>'Reply not found'],404);

    // Ownership check
    if($reply['author'] !== $currentUser && $currentUserRole !== 'teacher'){
        sendResponse(['error'=>'Permission denied'],403);
    }

    $stmt = $db->prepare("DELETE FROM replies WHERE reply_id=:rid");
    $stmt->bindParam(':rid',$replyId);
    if($stmt->execute()){
        sendResponse(['success'=>true,'message'=>'Reply deleted']);
    } else {
        sendResponse(['error'=>'Failed to delete reply'],500);
    }
}

// ==============================
// MAIN ROUTER
// ==============================
try {
    if(!isValidResource($resource)) sendResponse(['error'=>'Invalid resource'],400);

    switch($resource){
        case 'topics':
            switch($method){
                case 'GET':
                    if($id) getTopicById($db,$id);
                    else getAllTopics($db);
                    break;
                case 'POST':
                    createTopic($db,$input,$currentUser);
                    break;
                case 'PUT':
                    updateTopic($db,$input,$currentUser,$currentUserRole);
                    break;
                case 'DELETE':
                    if(!$id && empty($input['topic_id'])) sendResponse(['error'=>'Topic ID required'],400);
                    deleteTopic($db,$id ?? $input['topic_id'],$currentUser,$currentUserRole);
                    break;
                default:
                    sendResponse(['error'=>'Method not allowed'],405);
            }
            break;

        case 'replies':
            switch($method){
                case 'GET':
                    $topicId = $_GET['topic_id'] ?? null;
                    getRepliesByTopicId($db,$topicId);
                    break;
                case 'POST':
                    createReply($db,$input,$currentUser);
                    break;
                case 'DELETE':
                    if(!$id && empty($input['reply_id'])) sendResponse(['error'=>'Reply ID required'],400);
                    deleteReply($db,$id ?? $input['reply_id'],$currentUser,$currentUserRole);
                    break;
                default:
                    sendResponse(['error'=>'Method not allowed'],405);
            }
            break;
    }
} catch(PDOException $e){
    sendResponse(['error'=>'Database error'],500);
} catch(Exception $e){
    sendResponse(['error'=>'Server error'],500);
}

?>
