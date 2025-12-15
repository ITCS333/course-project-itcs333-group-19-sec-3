<?php
/**
 * Assignment Management API
 * 
 * This is a RESTful API that handles all CRUD operations for course assignments
 * and their associated discussion comments.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: assignments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - title (VARCHAR(200))
 *   - description (TEXT)
 *   - due_date (DATE)
 *   - files (TEXT)
 *   - created_at (TIMESTAMP)
 *   - updated_at (TIMESTAMP)
 * 
 * Table: comments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - assignment_id (VARCHAR(50), FOREIGN KEY)
 *   - author (VARCHAR(100))
 *   - text (TEXT)
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve assignment(s) or comment(s)
 *   - POST: Create a new assignment or comment
 *   - PUT: Update an existing assignment
 *   - DELETE: Delete an assignment or comment
 * 
 * Response Format: JSON
 */

// ============================================================================
// HEADERS AND CORS CONFIGURATION
// ============================================================================

//start session
session_start();

$_SESSION['initialized'] = true;


// TODO: Set Content-Type header to application/json
header('Content-Type: application/json');
// TODO: Set CORS headers to allow cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// TODO: Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'POST' || $_SERVER['REQUEST_METHOD'] == 'PUT' || $_SERVER['REQUEST_METHOD'] == 'DELETE') {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit();
    }
}

// ============================================================================
// DATABASE CONNECTION
// ============================================================================

require_once "../../config/Database.php";
// TODO: Create database connection
$database=new Database();
$db=$database->getConnection();
// TODO: Set PDO to throw exceptions on errors
   // already declared in the database file 

// ============================================================================
// REQUEST PARSING
// ============================================================================

// TODO: Get the HTTP request method
$method = $_SERVER['REQUEST_METHOD'];

// TODO: Get the request body for POST and PUT requests
$data = [];
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
         // Fallback for simple form POST or if JSON body is not provided
         $data = $_POST;
    }
} elseif ($method === 'PUT' || $method === 'DELETE') {
    // For PUT and DELETE, we expect a JSON body
    parse_str(file_get_contents("php://input"), $put_vars);
    $data = $put_vars;
    
    // Try JSON decode first for modern requests
    $json_data = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($json_data)) {
        $data = array_merge($data, $json_data);
    }
}

// TODO: Parse query parameters
$resource = isset($_GET['resource']) ? sanitizeInput($_GET['resource']) : null;



// ============================================================================
// ASSIGNMENT CRUD FUNCTIONS
// ============================================================================

/**
 * Function: Get all assignments
 * Method: GET
 * Endpoint: ?resource=assignments
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by title or description
 *   - sort: Optional field to sort by (title, due_date, created_at)
 *   - order: Optional sort order (asc or desc, default: asc)
 * 
 * Response: JSON array of assignment objects
 */
function getAllAssignments($db) {
    // TODO: Start building the SQL query
    $sql = "SELECT id, title, description, due_date, files, created_at, updated_at FROM assignments";
    $params = [];
    $where = [];
    
    // TODO: Check if 'search' query parameter exists in $_GET
    if (!empty($_GET['search'])) {
        $searchTerm = '%' . sanitizeInput($_GET['search']) . '%';
        $where[] = " (title LIKE :search OR description LIKE :search) ";
        $params[':search'] = $searchTerm;
    }
    
    if (!empty($where)) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    
    
    // TODO: Check if 'sort' and 'order' query parameters exist
    $allowedSort = ['title', 'due_date', 'created_at', 'id'];
    $allowedOrder = ['asc', 'desc'];
    
    $sort = validateAllowedValue($_GET['sort'] ?? '', $allowedSort) ? $_GET['sort'] : 'created_at';
    $order = validateAllowedValue($_GET['order'] ?? '', $allowedOrder) ? $_GET['order'] : 'DESC'; // Default to newest first

    $sql .= " ORDER BY {$sort} {$order}";

    
    // TODO: Prepare the SQL statement using $db->prepare()
    $stmt = $db->prepare($sql);
    
    // TODO: Bind parameters if search is used (handled automatically by $stmt->execute($params) above)
    
    // TODO: Execute the prepared statement
    $stmt->execute($params);
    
    // TODO: Fetch all results as associative array
    $assignments = $stmt->fetchAll();
    
    // TODO: For each assignment, decode the 'files' field from JSON to array
    foreach ($assignments as &$assignment) {
        $assignment['files'] = json_decode($assignment['files'], true) ?: [];
    }
    
    // TODO: Return JSON response
    sendResponse($assignments);
}


/**
 * Function: Get a single assignment by ID
 * Method: GET
 * Endpoint: ?resource=assignments&id={assignment_id}
 * 
 * Query Parameters:
 *   - id: The assignment ID (required)
 * 
 * Response: JSON object with assignment details
 */
function getAssignmentById($db, $assignmentId) {
    // TODO: Validate that $assignmentId is provided and not empty
    if(empty($assignmentId)){
        sendResponse(['error' => 'Assignment ID is required.'], 400);
    }
    
    // TODO: Prepare SQL query to select assignment by id
    $stmt = $db->prepare("SELECT id, title, description, due_date, files FROM assignments WHERE id = :id");

    
    // TODO: Bind the :id parameter
    $stmt->bindParam(':id', $assignmentId);
    
    // TODO: Execute the statement
    $stmt->execute();
    
    // TODO: Fetch the result as associative array
    $assignment = $stmt->fetch();

    
    // TODO: Check if assignment was found
    if (!$assignment) {
        sendResponse(['error' => 'Assignment not found.'], 404);
    }
    
    // TODO: Decode the 'files' field from JSON to array
    $assignment['files'] = json_decode($assignment['files'], true) ?: [];

    
    // TODO: Return success response with assignment data
     sendResponse($assignment);
}


/**
 * Function: Create a new assignment
 * Method: POST
 * Endpoint: ?resource=assignments
 * 
 * Required JSON Body:
 *   - title: Assignment title (required)
 *   - description: Assignment description (required)
 *   - due_date: Due date in YYYY-MM-DD format (required)
 *   - files: Array of file URLs/paths (optional)
 * 
 * Response: JSON object with created assignment data
 */
function createAssignment($db, $data) {
    // TODO: Validate required fields
    if (empty($data['title']) || empty($data['description']) || empty($data['due_date'])) {
        sendResponse(['error' => 'Title, description, and due date are required.'], 400);
    }
    
    // TODO: Sanitize input data
    $title = sanitizeInput($data['title']);
    $description = sanitizeInput($data['description']);
    $dueDate = sanitizeInput($data['due_date']);
    $files = $data['files'] ?? [];
    
    
    // TODO: Validate due_date format
    if (!validateDate($dueDate)) {
        sendResponse(['error' => 'Invalid due date format. Must be YYYY-MM-DD.'], 400);
    }
    
    // TODO: Generate a unique assignment ID
    //using AUTO_INCREMENT
    
    // TODO: Handle the 'files' field
    $filesJson = json_encode(array_map('sanitizeInput', (array)$files));

    
    // TODO: Prepare INSERT query
    $sql = "INSERT INTO assignments (title, description, due_date, files) VALUES (:title, :description, :due_date, :files)";
    $stmt = $db->prepare($sql);
    
    // TODO: Bind all parameters
    $success = $stmt->execute([
        ':title' => $title,
        ':description' => $description,
        ':due_date' => $dueDate,
        ':files' => $filesJson
    ]);
    
    // TODO: Execute the statement (handled above)
    
    
    // TODO: Check if insert was successful
    if ($success) {
        $newId = $db->lastInsertId();
        // Fetch the newly created assignment to return a complete object
        // Use the function created above to avoid code duplication
        $stmt = $db->prepare("SELECT id, title, description, due_date, files, created_at, updated_at FROM assignments WHERE id = :id");
        $stmt->execute([':id' => $newId]);
        $newAssignment = $stmt->fetch();
        $newAssignment['files'] = json_decode($newAssignment['files'], true) ?: [];

        sendResponse($newAssignment, 201);
    } else {
        // TODO: If insert failed, return 500 error
        sendResponse(['error' => 'Failed to create assignment.'], 500);
    }
}
        


/**
 * Function: Update an existing assignment
 * Method: PUT
 * Endpoint: ?resource=assignments
 * 
 * Required JSON Body:
 *   - id: Assignment ID (required, to identify which assignment to update)
 *   - title: Updated title (optional)
 *   - description: Updated description (optional)
 *   - due_date: Updated due date (optional)
 *   - files: Updated files array (optional)
 * 
 * Response: JSON object with success status
 */
function updateAssignment($db, $data) {
    // TODO: Validate that 'id' is provided in $data
    if (empty($data['id'])) {
        sendResponse(['error' => 'Assignment ID is required for update.'], 400);
    }
    
    // TODO: Store assignment ID in variable
        $assignmentId = sanitizeInput($data['id']);

    
    // TODO: Check if assignment exists
    $checkStmt = $db->prepare("SELECT COUNT(*) FROM assignments WHERE id = :id");
    $checkStmt->execute([':id' => $assignmentId]);
    if ($checkStmt->fetchColumn() == 0) {
        sendResponse(['error' => 'Assignment not found.'], 404);
    }
    
    $setClauses = [];
    $params = [':id' => $assignmentId];
    
    // TODO: Build UPDATE query dynamically based on provided fields
    if (!empty($data['title'])) {
        $setClauses[] = "title = :title";
        $params[':title'] = sanitizeInput($data['title']);
    }
    if (!empty($data['description'])) {
        $setClauses[] = "description = :description";
        $params[':description'] = sanitizeInput($data['description']);
    }
    if (!empty($data['due_date'])) {
        if (!validateDate($data['due_date'])) {
            sendResponse(['error' => 'Invalid due date format. Must be YYYY-MM-DD.'], 400);
        }
        $setClauses[] = "due_date = :due_date";
        $params[':due_date'] = sanitizeInput($data['due_date']);
    }
    if (isset($data['files'])) {
        // Handle files array, JSON encode it
        $filesJson = json_encode(array_map('sanitizeInput', (array)$data['files']));
        $setClauses[] = "files = :files";
        $params[':files'] = $filesJson;
    }
    
    // TODO: Check which fields are provided and add to SET clause
    
    
    // TODO: If no fields to update (besides updated_at), return 400 error
    if (empty($setClauses)) {
        sendResponse(['error' => 'No fields provided for update.'], 400);
    }
    
    // TODO: Complete the UPDATE query
    $sql = "UPDATE assignments SET " . implode(', ', $setClauses) . ", updated_at = NOW() WHERE id = :id";
    
    // TODO: Prepare the statement
    // TODO: Bind all parameters dynamically
    // TODO: Execute the statement
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    // TODO: Check if update was successful
    if ($stmt->rowCount() > 0) {
        sendResponse(['success' => true, 'message' => 'Assignment updated successfully.']);
    } else {
    // TODO: If no rows affected (meaning no change), return appropriate message
        sendResponse(['success' => true, 'message' => 'Assignment found, but no changes were applied.'], 200);
    }
}
    


/**
 * Function: Delete an assignment
 * Method: DELETE
 * Endpoint: ?resource=assignments&id={assignment_id}
 * 
 * Query Parameters:
 *   - id: Assignment ID (required)
 * 
 * Response: JSON object with success status
 */
function deleteAssignment($db, $assignmentId) {
    // TODO: Validate that $assignmentId is provided and not empty
    if (empty($assignmentId)) {
        sendResponse(['error' => 'Assignment ID is required for deletion.'], 400);
    }
    
    // TODO: Check if assignment exists
    if (!assignmentExists($db, $assignmentId)) {
        sendResponse(['error' => 'Assignment not found.'], 404);
    }
    
    // TODO: Delete associated comments first (due to foreign key constraint)
    $stmtComments = $db->prepare("DELETE FROM comments WHERE assignment_id = :id");
    $stmtComments->execute([':id' => $assignmentId]);
    
    // TODO: Prepare DELETE query for assignment
    // TODO: Bind the :id parameter
    // TODO: Execute the statement
    $stmtAssignment = $db->prepare("DELETE FROM assignments WHERE id = :id");
    $stmtAssignment->execute([':id' => $assignmentId]);
    
    // TODO: Check if delete was successful
    if ($stmtAssignment->rowCount() > 0) {
        sendResponse(['success' => true, 'message' => 'Assignment and associated comments deleted successfully.']);
    }
    
    // TODO: If delete failed, return 500 error
    else {
        sendResponse(['error' => 'Failed to delete assignment.'], 500);
    }
}


// ============================================================================
// COMMENT CRUD FUNCTIONS
// ============================================================================

/**
 * Function: Get all comments for a specific assignment
 * Method: GET
 * Endpoint: ?resource=comments&assignment_id={assignment_id}
 * 
 * Query Parameters:
 *   - assignment_id: The assignment ID (required)
 * 
 * Response: JSON array of comment objects
 */
function getCommentsByAssignment($db, $assignmentId) {
    // TODO: Validate that $assignmentId is provided and not empty
    if (empty($assignmentId)) {
        sendResponse(['error' => 'Assignment ID is required to fetch comments.'], 400);
    }
    
    // TODO: Prepare SQL query to select all comments for the assignment
    // TODO: Bind the :assignment_id parameter
    // TODO: Execute the statement
    // TODO: Fetch all results as associative array
    $stmt = $db->prepare("SELECT id, assignment_id, author, text, created_at FROM comments WHERE assignment_id = :assignment_id ORDER BY created_at ASC");
    $stmt->execute([':assignment_id' => $assignmentId]);
    $comments = $stmt->fetchAll();
    
    
    // TODO: Return success response with comments data
    sendResponse($comments);
}


/**
 * Function: Create a new comment
 * Method: POST
 * Endpoint: ?resource=comments
 * 
 * Required JSON Body:
 *   - assignment_id: Assignment ID (required)
 *   - author: Comment author name (required)
 *   - text: Comment content (required)
 * 
 * Response: JSON object with created comment data
 */
function createComment($db, $data) {
    // TODO: Validate required fields
    if (empty($data['assignment_id']) || empty($data['author']) || empty($data['text'])) {
        sendResponse(['error' => 'Assignment ID, author, and text are required to create a comment.'], 400);
    }
    
    // TODO: Sanitize input data
    $assignmentId = sanitizeInput($data['assignment_id']);
    $author = sanitizeInput($data['author']);
    $text = sanitizeInput($data['text']);
    
    // TODO: Validate that text is not empty after trimming
    if (!assignmentExists($db, $assignmentId)) {
        sendResponse(['error' => 'Cannot post comment: Assignment not found.'], 404);
    }
    
    // TODO: Verify that the assignment exists
    if (!assignmentExists($db, $assignmentId)) {
        sendResponse(['error' => 'Cannot post comment: Assignment not found.'], 404);
    }
    
    // TODO: Prepare INSERT query for comment
    $sql = "INSERT INTO comments (assignment_id, author, text) VALUES (:assignment_id, :author, :text)";
    $stmt = $db->prepare($sql);
    
    // TODO: Bind all parameters
    // TODO: Execute the statement
    $success = $stmt->execute([
        ':assignment_id' => $assignmentId,
        ':author' => $author,
        ':text' => $text
    ]);
    
    
    // TODO: Get the ID of the inserted comment
    if ($success) {
        $newId = $db->lastInsertId();
        
        // Fetch the newly created comment to return a complete object
        $stmt = $db->prepare("SELECT id, assignment_id, author, text, created_at FROM comments WHERE id = :id");
        $stmt->execute([':id' => $newId]);
        $newComment = $stmt->fetch();
    
    // TODO: Return success response with created comment data
    sendResponse($newComment, 201);
    } else {
        sendResponse(['error' => 'Failed to create comment.'], 500);
    }
}


/**
 * Function: Delete a comment
 * Method: DELETE
 * Endpoint: ?resource=comments&id={comment_id}
 * 
 * Query Parameters:
 *   - id: Comment ID (required)
 * 
 * Response: JSON object with success status
 */
function deleteComment($db, $commentId) {
    // TODO: Validate that $commentId is provided and not empty
    if (empty($commentId)) {
        sendResponse(['error' => 'Comment ID is required for deletion.'], 400);
    }
    
    // TODO: Check if comment exists
    
    
    // TODO: Prepare DELETE query
    // TODO: Bind the :id parameter
    // TODO: Execute the statement
    $stmt = $db->prepare("DELETE FROM comments WHERE id = :id");
    $stmt->execute([':id' => $commentId]);
    
    
    // TODO: Check if delete was successful
    if ($stmt->rowCount() > 0) {
        sendResponse(['success' => true, 'message' => 'Comment deleted successfully.']);
    } elseif ($stmt->rowCount() === 0) {
        sendResponse(['error' => 'Comment not found or already deleted.'], 404);
    } else {
    // TODO: If delete failed, return 500 error
        sendResponse(['error' => 'Failed to delete comment.'], 500);
    }
    
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Get the 'resource' query parameter to determine which resource to access
    $resource = isset($_GET['resource']) ? sanitizeInput($_GET['resource']) : null;
    
    if (!$resource) {
        sendResponse(['error' => 'Resource parameter is missing.'], 400);
    }
    
    // TODO: Route based on HTTP method and resource type
    
    if ($method === 'GET') {
        // TODO: Handle GET requests
        
        if ($resource === 'assignments') {
            // TODO: Check if 'id' query parameter exists
            if (!empty($_GET['id'])) {
                getAssignmentById($db, sanitizeInput($_GET['id']));
            } else {
                getAllAssignments($db);
            }

        } elseif ($resource === 'comments') {
            // TODO: Check if 'assignment_id' query parameter exists
            if (!empty($_GET['assignment_id'])) {
                getCommentsByAssignment($db, sanitizeInput($_GET['assignment_id']));
            } else {
                sendResponse(['error' => 'assignment_id parameter is required for comments.'], 400);
            }

        } else {
            // TODO: Invalid resource, return 400 error
            sendResponse(['error' => 'Invalid resource requested.'], 400);
        }
        
    } elseif ($method === 'POST') {
        // TODO: Handle POST requests (create operations)
        
        if ($resource === 'assignments') {
            // TODO: Call createAssignment($db, $data)
            createAssignment($db, $data);

        } elseif ($resource === 'comments') {
            // TODO: Call createComment($db, $data)
            createComment($db, $data);

        } else {
            // TODO: Invalid resource, return 400 error
            sendResponse(['error' => 'Invalid resource for POST.'], 400);
        }
        
    } elseif ($method === 'PUT') {
        // TODO: Handle PUT requests (update operations)
        
        if ($resource === 'assignments') {
            // TODO: Call updateAssignment($db, $data)
            updateAssignment($db, $data);

        } else {
            // TODO: PUT not supported for other resources
            sendResponse(['error' => 'PUT not supported for this resource.'], 400);
        }
        
    } elseif ($method === 'DELETE') {
        // TODO: Handle DELETE requests
        
        if ($resource === 'assignments') {
            // TODO: Get 'id' from query parameter or request body
            $id = $_GET['id'] ?? ($data['id'] ?? null);
            if (!$id) {
                sendResponse(['error' => 'Assignment ID is required.'], 400);
            }
            deleteAssignment($db, sanitizeInput($id));

        } elseif ($resource === 'comments') {
            // TODO: Get comment 'id' from query parameter
            if (empty($_GET['id'])) {
                sendResponse(['error' => 'Comment ID is required.'], 400);
            }
            deleteComment($db, sanitizeInput($_GET['id']));


        } else {
            // TODO: Invalid resource, return 400 error
            sendResponse(['error' => 'Invalid resource for DELETE.'], 400);
        }
        
    } else {
        // TODO: Method not supported
        sendResponse(['error' => 'HTTP method not supported.'], 405);
    }
    
} catch (PDOException $e) {
    // TODO: Handle database errors
     sendResponse(['error' => 'Database error: ' . $e->getMessage()], 500);

} catch (Exception $e) {
    // TODO: Handle general errors
    sendResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper function to send JSON response and exit
 * 
 * @param array $data - Data to send as JSON
 * @param int $statusCode - HTTP status code (default: 200)
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
    http_response_code($statusCode);
    
    // TODO: Ensure data is an array
    if (!is_array($data)) {
        $data = ['message' => $data];
    }
    
    // TODO: Echo JSON encoded data
    header('Content-Type: application/json');
    echo json_encode($data);

    // TODO: Exit to prevent further execution
    exit;
    
}


/**
 * Helper function to sanitize string input
 * 
 * @param string $data - Input data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Trim whitespace from beginning and end
    $data = trim($data);
    
    // TODO: Remove HTML and PHP tags
    $data = strip_tags($data);

    
    // TODO: Convert special characters to HTML entities
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    // TODO: Return the sanitized data
    return $data;
}


/**
 * Helper function to validate date format (YYYY-MM-DD)
 * 
 * @param string $date - Date string to validate
 * @return bool - True if valid, false otherwise
 */
function validateDate($date) {
    // TODO: Use DateTime::createFromFormat to validate
    $d = DateTime::createFromFormat('Y-m-d', $date);
    
    // TODO: Return true if valid, false otherwise
    return $d && $d->format('Y-m-d') === $date;
}


/**
 * Helper function to validate allowed values (for sort fields, order, etc.)
 * 
 * @param string $value - Value to validate
 * @param array $allowedValues - Array of allowed values
 * @return bool - True if valid, false otherwise
 */
function validateAllowedValue($value, $allowedValues) {
    // TODO: Check if $value exists in $allowedValues array
    $isValid = in_array($value, $allowedValues, true);
    
    // TODO: Return the result
    return $isValid;
}

?>
