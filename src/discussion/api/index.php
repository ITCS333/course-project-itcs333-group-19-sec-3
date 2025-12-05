<?php
session_start();

// ------------------------------
// Simulated User Session (replace with real auth in production)
// ------------------------------
$currentUser = $_SESSION['username'] ?? null; // e.g. "Ali Hassan"
$currentUserRole = $_SESSION['role'] ?? null; // e.g. "teacher" or "student"

// ------------------------------
// Headers for JSON response and CORS
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
// Include Database Connection (adjust path if needed)
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

function isValidResource($resource) {
    $allowed = ['topics', 'replies'];
    return in_array($resource, $allowed);
}

function getJsonPayload() {
    $body = file_get_contents('php://input');
    if ($body) {
        $decoded = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }
        parse_str($body, $parsed);
        if (!empty($parsed)) return $parsed;
    }
    if (!empty($_POST)) return $_POST;
    return [];
}

// ------------------------------
// TOPICS
// ------------------------------
function getAllTopics($db) {
    $search = $_GET['search'] ?? '';
    $sort = $_GET['sort'] ?? 'created_at';
    $order = strtolower($_GET['order'] ?? 'desc');

    $allowedSort = ['subject', 'author', 'created_at'];
    if (!in_array($sort, $allowedSort)) $sort = 'created_at';
    if (!in_array($order, ['asc','desc'])) $order = 'desc';

    $params = [];
    $sql = "SELECT id, subject, message, author, DATE_FORMAT(created_at,'%Y-%m-%d %H:%i:%s') as created_at FROM topics";

    if ($search) {
        $sql .= " WHERE subject LIKE :s OR message LIKE :s OR author LIKE :s";
        $params[':s'] = "%$search%";
    }

    $sql .= " ORDER BY $sort $order";
    $stmt = $db->prepare($sql);
    foreach ($params as $k=>$v) $stmt->bindValue($k,$v);
    $stmt->execute();
    $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse(['success'=>true,'data'=>$topics]);
}

function getTopicById($db, $topicId) {
    if (!$topicId) sendResponse(['success'=>false,'error'=>'Topic ID required'], 400);

    $stmt = $db->prepare("SELECT id, subject, message, author, DATE_FORMAT(created_at,'%Y-%m-%d %H:%i:%s') as created_at FROM topics WHERE id = :tid LIMIT 1");
    $stmt->bindParam(':tid', $topicId, PDO::PARAM_INT);
    $stmt->execute();
    $topic = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($topic) sendResponse(['success'=>true,'data'=>$topic]);
    sendResponse(['success'=>false,'error'=>'Topic not found'],404);
}

function createTopic($db, $data, $currentUser) {
    // allow author from session or payload (for testing)
    $author = $currentUser ?? ($data['author'] ?? null);

    if (!$author) sendResponse(['success'=>false,'error'=>'Unauthorized: author required'],401);

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
        // Return the newly created topic (convenient for front-end)
        $stmt2 = $db->prepare("SELECT id, subject, message, author, DATE_FORMAT(created_at,'%Y-%m-%d %H:%i:%s') as created_at FROM topics WHERE id = :id LIMIT 1");
        $stmt2->bindParam(':id',$newId,PDO::PARAM_INT);
        $stmt2->execute();
        $topic = $stmt2->fetch(PDO::FETCH_ASSOC);
        sendResponse(['success'=>true,'data'=>$topic],201);
    } else {
        sendResponse(['success'=>false,'error'=>'Failed to create topic'],500);
    }
}

function updateTopic($db, $data, $currentUser, $currentUserRole) {
    if (empty($data['id'])) sendResponse(['success'=>false,'error'=>'id required'],400);
    $id = (int)$data['id'];

    // fetch topic author
    $stmt = $db->prepare("SELECT author FROM topics WHERE id = :id LIMIT 1");
    $stmt->bindParam(':id',$id,PDO::PARAM_INT);
    $stmt->execute();
    $topic = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$topic) sendResponse(['success'=>false,'error'=>'Topic not found'],404);

    $author = $topic['author'];
    // permission: owner or teacher
    $isTeacher = ($currentUserRole === 'teacher' || $currentUserRole === 'admin');
    if (($currentUser !== $author) && !$isTeacher) {
        sendResponse(['success'=>false,'error'=>'Permission denied'],403);
    }

    $fields = [];
    $params = [':id'=>$id];
    if (isset($data['subject'])) {
        $fields[] = 'subject=:sub';
        $params[':sub'] = sanitizeInput($data['subject']);
    }
    if (isset($data['message'])) {
        $fields[] = 'message=:msg';
        $params[':msg'] = sanitizeInput($data['message']);
    }
    if (empty($fields)) sendResponse(['success'=>false,'error'=>'Nothing to update'],400);

    $sql = "UPDATE topics SET ".implode(', ',$fields)." WHERE id=:id";
    $stmt = $db->prepare($sql);
    foreach ($params as $k=>$v) $stmt->bindValue($k,$v);
    if ($stmt->execute()) {
        sendResponse(['success'=>true,'message'=>'Topic updated']);
    } else {
        sendResponse(['success'=>false,'error'=>'Failed to update topic'],500);
    }
}

function deleteTopic($db, $topicId, $currentUser, $currentUserRole) {
    if (!$topicId) sendResponse(['success'=>false,'error'=>'Topic ID required'],400);
    $tid = (int)$topicId;

    // fetch topic
    $stmt = $db->prepare("SELECT author FROM topics WHERE id = :id LIMIT 1");
    $stmt->bindParam(':id',$tid,PDO::PARAM_INT);
    $stmt->execute();
    $topic = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$topic) sendResponse(['success'=>false,'error'=>'Topic not found'],404);

    $author = $topic['author'];
    $isTeacher = ($currentUserRole === 'teacher' || $currentUserRole === 'admin');
    if (($currentUser !== $author) && !$isTeacher) {
        sendResponse(['success'=>false,'error'=>'Permission denied'],403);
    }

    // delete replies (FK ON DELETE CASCADE would also do it; explicitly here to be safe)
    $stmt = $db->prepare("DELETE FROM replies WHERE topic_id = :tid");
    $stmt->bindParam(':tid',$tid,PDO::PARAM_INT);
    $stmt->execute();

    $stmt = $db->prepare("DELETE FROM topics WHERE id = :id");
    $stmt->bindParam(':id',$tid,PDO::PARAM_INT);
    if ($stmt->execute()) {
        sendResponse(['success'=>true,'message'=>'Topic deleted']);
    } else {
        sendResponse(['success'=>false,'error'=>'Failed to delete topic'],500);
    }
}

// ------------------------------
// REPLIES
// ------------------------------
function getRepliesByTopicId($db, $topicId) {
    if (!$topicId) sendResponse(['success'=>false,'error'=>'Topic ID required'],400);
    $tid = (int)$topicId;

    $stmt = $db->prepare("SELECT id, topic_id, text, author, DATE_FORMAT(created_at,'%Y-%m-%d %H:%i:%s') as created_at FROM replies WHERE topic_id = :tid ORDER BY created_at ASC");
    $stmt->bindParam(':tid',$tid,PDO::PARAM_INT);
    $stmt->execute();
    $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse(['success'=>true,'data'=>$replies]);
}

function createReply($db, $data, $currentUser) {
    $author = $currentUser ?? ($data['author'] ?? null);
    if (!$author) sendResponse(['success'=>false,'error'=>'Unauthorized: author required'],401);

    $topic_id = isset($data['topic_id']) ? (int)$data['topic_id'] : 0;
    $text = trim($data['text'] ?? '');
    if ($topic_id <= 0 || $text === '') sendResponse(['success'=>false,'error'=>'topic_id and text are required'],400);

    // ensure topic exists
    $stmt = $db->prepare("SELECT id FROM topics WHERE id = :id LIMIT 1");
    $stmt->bindParam(':id',$topic_id,PDO::PARAM_INT);
    $stmt->execute();
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) sendResponse(['success'=>false,'error'=>'Topic not found'],404);

    $text_s = sanitizeInput($text);
    $author_s = sanitizeInput($author);

    $stmt = $db->prepare("INSERT INTO replies (topic_id, text, author) VALUES (:tid, :txt, :auth)");
    $stmt->bindParam(':tid',$topic_id,PDO::PARAM_INT);
    $stmt->bindParam(':txt',$text_s);
    $stmt->bindParam(':auth',$author_s);

    if ($stmt->execute()) {
        $newId = (int)$db->lastInsertId();
        $stmt2 = $db->prepare("SELECT id, topic_id, text, author, DATE_FORMAT(created_at,'%Y-%m-%d %H:%i:%s') as created_at FROM replies WHERE id = :id LIMIT 1");
        $stmt2->bindParam(':id',$newId,PDO::PARAM_INT);
        $stmt2->execute();
        $reply = $stmt2->fetch(PDO::FETCH_ASSOC);
        sendResponse(['success'=>true,'data'=>$reply],201);
    } else {
        sendResponse(['success'=>false,'error'=>'Failed to create reply'],500);
    }
}

function deleteReply($db, $replyId, $currentUser, $currentUserRole) {
    if (!$replyId) sendResponse(['success'=>false,'error'=>'Reply ID required'],400);
    $rid = (int)$replyId;

    $stmt = $db->prepare("SELECT author FROM replies WHERE id = :id LIMIT 1");
    $stmt->bindParam(':id',$rid,PDO::PARAM_INT);
    $stmt->execute();
    $reply = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$reply) sendResponse(['success'=>false,'error'=>'Reply not found'],404);

    $author = $reply['author'];
    $isTeacher = ($currentUserRole === 'teacher' || $currentUserRole === 'admin');
    if (($currentUser !== $author) && !$isTeacher) {
        sendResponse(['success'=>false,'error'=>'Permission denied'],403);
    }

    $stmt = $db->prepare("DELETE FROM replies WHERE id = :id");
    $stmt->bindParam(':id',$rid,PDO::PARAM_INT);
    if ($stmt->execute()) {
        sendResponse(['success'=>true,'message'=>'Reply deleted']);
    } else {
        sendResponse(['success'=>false,'error'=>'Failed to delete reply'],500);
    }
}

// ------------------------------
// Router
// ------------------------------
try {
    $method = $_SERVER['REQUEST_METHOD'];
    $resource = $_GET['resource'] ?? null;
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
    $payload = getJsonPayload();

    if (!isValidResource($resource)) sendResponse(['success'=>false,'error'=>'Invalid resource'],400);

    switch ($resource) {
        case 'topics':
            switch ($method) {
                case 'GET':
                    if ($id) getTopicById($db, $id);
                    else getAllTopics($db);
                    break;
                case 'POST':
                    createTopic($db, $payload, $currentUser);
                    break;
                case 'PUT':
                    updateTopic($db, $payload, $currentUser, $currentUserRole);
                    break;
                case 'DELETE':
                    // allow id in query or payload
                    if (!$id && empty($payload['id'])) sendResponse(['success'=>false,'error'=>'Topic ID required'],400);
                    deleteTopic($db, $id ?? $payload['id'], $currentUser, $currentUserRole);
                    break;
                default:
                    sendResponse(['success'=>false,'error'=>'Method not allowed'],405);
            }
            break;

        case 'replies':
            switch ($method) {
                case 'GET':
                    $topicId = $_GET['topic_id'] ?? null;
                    getRepliesByTopicId($db, $topicId);
                    break;
                case 'POST':
                    createReply($db, $payload, $currentUser);
                    break;
                case 'DELETE':
                    if (!$id && empty($payload['id'])) sendResponse(['success'=>false,'error'=>'Reply ID required'],400);
                    deleteReply($db, $id ?? $payload['id'], $currentUser, $currentUserRole);
                    break;
                default:
                    sendResponse(['success'=>false,'error'=>'Method not allowed'],405);
            }
            break;
    }
} catch (PDOException $e) {
    // optionally log $e->getMessage()
    sendResponse(['success'=>false,'error'=>'Database error'],500);
} catch (Exception $e) {
    sendResponse(['success'=>false,'error'=>'Server error'],500);
}
?>
