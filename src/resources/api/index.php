<?php
/**
 * Course Resources API
 * 
 * This is a RESTful API that handles all CRUD operations for course resources 
 * and their associated comments/discussions.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: resources
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - title (VARCHAR(255))
 *   - description (TEXT)
 *   - link (VARCHAR(500))
 *   - created_at (TIMESTAMP)
 * 
 * Table: comments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - resource_id (INT, FOREIGN KEY references resources.id)
 *   - author (VARCHAR(100))
 *   - text (TEXT)
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve resource(s) or comment(s)
 *   - POST: Create a new resource or comment
 *   - PUT: Update an existing resource
 *   - DELETE: Delete a resource or comment
 * 
 * Response Format: JSON
 * 
 * API Endpoints:
 *   Resources:
 *     GET    /api/resources.php                    - Get all resources
 *     GET    /api/resources.php?id={id}           - Get single resource by ID
 *     POST   /api/resources.php                    - Create new resource
 *     PUT    /api/resources.php                    - Update resource
 *     DELETE /api/resources.php?id={id}           - Delete resource
 * 
 *   Comments:
 *     GET    /api/resources.php?resource_id={id}&action=comments  - Get comments for resource
 *     POST   /api/resources.php?action=comment                    - Create new comment
 *     DELETE /api/resources.php?comment_id={id}&action=delete_comment - Delete comment
 */

// ============================================================================
 // HEADERS AND INITIALIZATION
 // ============================================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include the database connection function
require_once '../../db.php';

// Get the PDO database connection
$db = getDBConnection();
if (!$db) {
    sendResponse(['success' => false, 'message' => 'Database connection failed'], 500);
}

// Get the HTTP request method
$method = $_SERVER['REQUEST_METHOD'];

// Get the request body (for POST, PUT, DELETE with JSON)
$rawInput = file_get_contents('php://input');
$bodyData = json_decode($rawInput, true);

// If JSON decoding failed or body is empty, fall back to $_POST for POST requests
if (!is_array($bodyData)) {
    $bodyData = $_POST ?? [];
}

// Parse query parameters
$action     = $_GET['action']      ?? null;
$idParam    = $_GET['id']          ?? null;
$resourceId = $_GET['resource_id'] ?? null;
$commentId  = $_GET['comment_id']  ?? null;

// ============================================================================
// RESOURCE FUNCTIONS
// ============================================================================

function getAllResources($db) {
    // Base SQL
    $sql = "SELECT id, title, description, link, created_at FROM resources";
    $params = [];

    // Search
    $search = $_GET['search'] ?? null;
    if (!empty($search)) {
        $sql .= " WHERE title LIKE :term OR description LIKE :term";
        $params[':term'] = '%' . $search . '%';
    }

    // Sort
    $allowedSort = ['title', 'created_at'];
    $sort = $_GET['sort'] ?? 'created_at';
    if (!in_array($sort, $allowedSort, true)) {
        $sort = 'created_at';
    }

    // Order
    $order = strtolower($_GET['order'] ?? 'desc');
    if (!in_array($order, ['asc', 'desc'], true)) {
        $order = 'desc';
    }

    $sql .= " ORDER BY {$sort} {$order}";

    $stmt = $db->prepare($sql);

    if (isset($params[':term'])) {
        $stmt->bindValue(':term', $params[':term'], PDO::PARAM_STR);
    }

    $stmt->execute();
    $resources = $stmt->fetchAll();

    sendResponse([
        'success' => true,
        'data'    => $resources
    ]);
}


function getResourceById($db, $resourceId) {
    // Validate ID
    if (empty($resourceId) || !ctype_digit((string)$resourceId)) {
        sendResponse(['success' => false, 'message' => 'Invalid resource ID'], 400);
    }

    $stmt = $db->prepare(
        "SELECT id, title, description, link, created_at 
         FROM resources 
         WHERE id = ?"
    );
    $stmt->execute([$resourceId]);
    $resource = $stmt->fetch();

    if ($resource) {
        sendResponse(['success' => true, 'data' => $resource]);
    } else {
        sendResponse(['success' => false, 'message' => 'Resource not found'], 404);
    }
}


function createResource($db, $data) {
    // Validate required fields
    $validation = validateRequiredFields($data, ['title', 'link']);
    if (!$validation['valid']) {
        sendResponse([
            'success' => false,
            'message' => 'Missing required fields',
            'missing' => $validation['missing']
        ], 400);
    }

    // Sanitize and validate
    $title = sanitizeInput($data['title']);
    $link  = trim($data['link']);
    $description = isset($data['description']) ? sanitizeInput($data['description']) : '';

    if (!validateUrl($link)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid URL format for link'
        ], 400);
    }

    // Insert
    $stmt = $db->prepare(
        "INSERT INTO resources (title, description, link) 
         VALUES (?, ?, ?)"
    );

    $ok = $stmt->execute([$title, $description, $link]);

    if ($ok) {
        $newId = $db->lastInsertId();
        sendResponse([
            'success' => true,
            'message' => 'Resource created successfully',
            'id'      => (int)$newId
        ], 201);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to create resource'
        ], 500);
    }
}


function updateResource($db, $data) {
    // Must have ID
    if (empty($data['id']) || !ctype_digit((string)$data['id'])) {
        sendResponse(['success' => false, 'message' => 'Invalid or missing resource ID'], 400);
    }

    $resourceId = (int)$data['id'];

    // Check existence
    $checkStmt = $db->prepare("SELECT id FROM resources WHERE id = ?");
    $checkStmt->execute([$resourceId]);
    if (!$checkStmt->fetch()) {
        sendResponse(['success' => false, 'message' => 'Resource not found'], 404);
    }

    $fields = [];
    $values = [];

    if (isset($data['title'])) {
        $fields[] = "title = ?";
        $values[] = sanitizeInput($data['title']);
    }

    if (isset($data['description'])) {
        $fields[] = "description = ?";
        $values[] = sanitizeInput($data['description']);
    }

    if (isset($data['link'])) {
        $link = trim($data['link']);
        if (!validateUrl($link)) {
            sendResponse(['success' => false, 'message' => 'Invalid URL format'], 400);
        }
        $fields[] = "link = ?";
        $values[] = $link;
    }

    if (empty($fields)) {
        sendResponse(['success' => false, 'message' => 'No fields to update'], 400);
    }

    $sql = "UPDATE resources SET " . implode(', ', $fields) . " WHERE id = ?";
    $values[] = $resourceId;

    $stmt = $db->prepare($sql);
    $ok = $stmt->execute($values);

    if ($ok) {
        sendResponse([
            'success' => true,
            'message' => 'Resource updated successfully'
        ]);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to update resource'
        ], 500);
    }
}


function deleteResource($db, $resourceId) {
    // Validate ID
    if (empty($resourceId) || !ctype_digit((string)$resourceId)) {
        sendResponse(['success' => false, 'message' => 'Invalid resource ID'], 400);
    }

    $resourceId = (int)$resourceId;

    // Check existence
    $checkStmt = $db->prepare("SELECT id FROM resources WHERE id = ?");
    $checkStmt->execute([$resourceId]);
    if (!$checkStmt->fetch()) {
        sendResponse(['success' => false, 'message' => 'Resource not found'], 404);
    }

    // Transaction
    try {
        $db->beginTransaction();

        // Delete comments first (comments_resource table)
        $delComments = $db->prepare("DELETE FROM comments_resource WHERE resource_id = ?");
        $delComments->execute([$resourceId]);

        // Delete resource
        $delResource = $db->prepare("DELETE FROM resources WHERE id = ?");
        $delResource->execute([$resourceId]);

        $db->commit();

        sendResponse([
            'success' => true,
            'message' => 'Resource and associated comments deleted successfully'
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Delete resource error: " . $e->getMessage());
        sendResponse([
            'success' => false,
            'message' => 'Failed to delete resource'
        ], 500);
    }
}


// ============================================================================
// COMMENT FUNCTIONS
// ============================================================================

function getCommentsByResourceId($db, $resourceId) {
    // Validate ID
    if (empty($resourceId) || !ctype_digit((string)$resourceId)) {
        sendResponse(['success' => false, 'message' => 'Invalid resource ID'], 400);
    }

    $resourceId = (int)$resourceId;

    $stmt = $db->prepare(
        "SELECT id, resource_id, author, text, created_at
         FROM comments_resource
         WHERE resource_id = ?
         ORDER BY created_at ASC"
    );
    $stmt->execute([$resourceId]);
    $comments = $stmt->fetchAll();

    sendResponse(['success' => true, 'data' => $comments]);
}


function createComment($db, $data) {
    // Validate required fields
    $validation = validateRequiredFields($data, ['resource_id', 'author', 'text']);
    if (!$validation['valid']) {
        sendResponse([
            'success' => false,
            'message' => 'Missing required fields',
            'missing' => $validation['missing']
        ], 400);
    }

    // Validate resource_id
    if (!ctype_digit((string)$data['resource_id'])) {
        sendResponse(['success' => false, 'message' => 'Invalid resource_id'], 400);
    }
    $resourceId = (int)$data['resource_id'];

    // Check resource exists
    $checkStmt = $db->prepare("SELECT id FROM resources WHERE id = ?");
    $checkStmt->execute([$resourceId]);
    if (!$checkStmt->fetch()) {
        sendResponse(['success' => false, 'message' => 'Resource not found'], 404);
    }

    $author = sanitizeInput($data['author']);
    $text   = sanitizeInput($data['text']);

    $stmt = $db->prepare(
        "INSERT INTO comments_resource (resource_id, author, text)
         VALUES (?, ?, ?)"
    );
    $ok = $stmt->execute([$resourceId, $author, $text]);

    if ($ok) {
        $newId = $db->lastInsertId();
        sendResponse([
            'success' => true,
            'message' => 'Comment added successfully',
            'id'      => (int)$newId
        ], 201);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to add comment'
        ], 500);
    }
}


function deleteComment($db, $commentId) {
    // Validate comment_id
    if (empty($commentId) || !ctype_digit((string)$commentId)) {
        sendResponse(['success' => false, 'message' => 'Invalid comment ID'], 400);
    }

    $commentId = (int)$commentId;

    // Check existence
    $checkStmt = $db->prepare("SELECT id FROM comments_resource WHERE id = ?");
    $checkStmt->execute([$commentId]);
    if (!$checkStmt->fetch()) {
        sendResponse(['success' => false, 'message' => 'Comment not found'], 404);
    }

    $delStmt = $db->prepare("DELETE FROM comments_resource WHERE id = ?");
    $ok = $delStmt->execute([$commentId]);

    if ($ok) {
        sendResponse([
            'success' => true,
            'message' => 'Comment deleted successfully'
        ]);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to delete comment'
        ], 500);
    }
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    if ($method === 'GET') {
        // Comments for a resource
        if ($action === 'comments') {
            $rid = $resourceId ?? $idParam;
            getCommentsByResourceId($db, $rid);
        }

        // Single resource
        if (!empty($idParam)) {
            getResourceById($db, $idParam);
        }

        // All resources
        getAllResources($db);

    } elseif ($method === 'POST') {
        // Create comment
        if ($action === 'comment') {
            createComment($db, $bodyData);
        }

        // Create resource
        createResource($db, $bodyData);

    } elseif ($method === 'PUT') {
        // Update resource
        updateResource($db, $bodyData);

    } elseif ($method === 'DELETE') {
        if ($action === 'delete_comment') {
            // Delete a comment
            $cid = $commentId ?? ($bodyData['comment_id'] ?? null);
            deleteComment($db, $cid);
        } else {
            // Delete a resource
            $rid = $idParam ?? ($bodyData['id'] ?? null);
            deleteResource($db, $rid);
        }
    } else {
        // Unsupported method
        sendResponse([
            'success' => false,
            'message' => 'Method not allowed'
        ], 405);
    }

} catch (PDOException $e) {
    error_log("PDO Error: " . $e->getMessage());
    sendResponse([
        'success' => false,
        'message' => 'Database error occurred'
    ], 500);

} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    sendResponse([
        'success' => false,
        'message' => 'An unexpected error occurred'
    ], 500);
}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);

    if (!is_array($data)) {
        $data = ['data' => $data];
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function validateUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = strip_tags($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function validateRequiredFields($data, $requiredFields) {
    $missing = [];

    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
            $missing[] = $field;
        }
    }

    return [
        'valid'   => count($missing) === 0,
        'missing' => $missing
    ];
}

?>