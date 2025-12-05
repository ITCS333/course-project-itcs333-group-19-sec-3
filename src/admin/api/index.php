<?php
/**
 * Student Management API
 * 
 * This is a RESTful API that handles all CRUD operations for student management.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structure (for reference):
 * Table: students
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - student_id (VARCHAR(50), UNIQUE) - The student's university ID
 *   - name (VARCHAR(100))
 *   - email (VARCHAR(100), UNIQUE)
 *   - password (VARCHAR(255)) - Hashed password
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve student(s)
 *   - POST: Create a new student OR change password
 *   - PUT: Update an existing student
 *   - DELETE: Delete a student
 * 
 * Response Format: JSON
 */


// TODO: Set headers for JSON response and CORS
// Set Content-Type to application/json
// Allow cross-origin requests (CORS) if needed
// Allow specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS)
// Allow specific headers (Content-Type, Authorization)
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit; 
}


// TODO: Handle preflight OPTIONS request
// If the request method is OPTIONS, return 200 status and exit
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// TODO: Include the database connection class
// Assume the Database class has a method getConnection() that returns a PDO instance
require __DIR__ . '/../../../db.php';
// TODO: Get the PDO database connection
$db = getDBConnection();

// TODO: Get the HTTP request method
// Use $_SERVER['REQUEST_METHOD']
$method = $_SERVER['REQUEST_METHOD'];

// TODO: Get the request body for POST and PUT requests
// Use file_get_contents('php://input') to get raw POST data
// Decode JSON data using json_decode()
$input = file_get_contents('php://input'); 
$data = json_decode($input, true);

// TODO: Parse query parameters for filtering and searching
$search = $_GET['search'] ?? null; 
$id     = $_GET['id'] ?? null;

/**
 * Function: Get all students or search for specific students
 * Method: GET
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by name, student_id, or email
 *   - sort: Optional field to sort by (name, student_id, email)
 *   - order: Optional sort order (asc or desc)
 */
function getStudents($db) {
    // TODO: Check if search parameter exists
    // If yes, prepare SQL query with WHERE clause using LIKE
    // Search should work on name, student_id, and email fields
    
    // TODO: Check if sort and order parameters exist
    // If yes, add ORDER BY clause to the query
    // Validate sort field to prevent SQL injection (only allow: name, student_id, email)
    // Validate order to prevent SQL injection (only allow: asc, desc)
    $search = $_GET['search'] ?? null;
    $sort   = $_GET['sort'] ?? null;
    $order  = $_GET['order'] ?? 'asc';
     $allowedSort = ['name', 'student_id', 'email'];
    if (!in_array($sort, $allowedSort)) {
        $sort = 'name';
    }
    $order = strtolower($order);
    if (!in_array($order, ['asc', 'desc'])) {
        $order = 'asc';
    }
    $sql = "SELECT student_id, name, email, created_at FROM students";
    $params = [];
    if ($search) {
        $sql .= " WHERE name LIKE :search OR student_id LIKE :search OR email LIKE :search";
        $params['search'] = "%$search%";
    }
    $sql .= " ORDER BY $sort $order";

    // TODO: Prepare the SQL query using PDO
    // Note: Do NOT select the password field
    $stmt = $db->prepare($sql);
    
    // TODO: Bind parameters if using search
    if ($search) {
        $stmt->bindParam(':search', $params['search'], PDO::PARAM_STR);
    }
    // TODO: Execute the query
    $stmt->execute();

    // TODO: Fetch all results as an associative array
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // TODO: Return JSON response with success status and data
    echo json_encode([
        "success" => true,
        "data" => $students
    ]);
}


/**
 * Function: Get a single student by student_id
 * Method: GET
 * 
 * Query Parameters:
 *   - student_id: The student's university ID
 */
function getStudentById($db, $studentId) {
    // TODO: Prepare SQL query to select student by student_id
    $sql = "SELECT student_id, name, email, created_at FROM students WHERE student_id = :student_id LIMIT 1";
    $stmt = $db->prepare($sql);
    // TODO: Bind the student_id parameter
    $stmt->bindParam(':student_id', $studentId, PDO::PARAM_STR);

    // TODO: Execute the query
    $stmt->execute();

    // TODO: Fetch the result
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    // TODO: Check if student exists
    // If yes, return success response with student data
    // If no, return error response with 404 status
    if ($student) {
        echo json_encode([
            "success" => true,
            "data" => $student
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Student not found"
        ]);
    }
}


/**
 * Function: Create a new student
 * Method: POST
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (must be unique)
 *   - name: Student's full name
 *   - email: Student's email (must be unique)
 *   - password: Default password (will be hashed)
 */
function createStudent($db, $data) {
    // TODO: Validate required fields
    // Check if student_id, name, email, and password are provided
    // If any field is missing, return error response with 400 status
    $student_id = $data['student_id'] ?? null;
    $name = $data['name'] ?? null;
    $email = $data['email'] ?? null;
    $password = $data['password'] ?? null;

    if (!$student_id || !$name || !$email || !$password) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Missing required fields"
        ]);
        return;
    }
    // TODO: Sanitize input data
    // Trim whitespace from all fields
    // Validate email format using filter_var()
    $student_id = trim($student_id);
    $name = trim($name);
    $email = trim($email);
    $password = trim($password);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid email format"
        ]);
        return;
    }
    // TODO: Check if student_id or email already exists
    // Prepare and execute a SELECT query to check for duplicates
    // If duplicate found, return error response with 409 status (Conflict)
    $checkSql = "SELECT * FROM students WHERE student_id = :student_id OR email = :email LIMIT 1";
    $stmt = $db->prepare($checkSql);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(409); 
        echo json_encode([
            "success" => false,
            "message" => "Student ID or email already exists"
        ]);
        return;
    }
    // TODO: Hash the password
    // Use password_hash() with PASSWORD_DEFAULT
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // TODO: Prepare INSERT query
    $insertSql = "INSERT INTO students (student_id, name, email, password, created_at)
                  VALUES (:student_id, :name, :email, :password, NOW())";
    $insertStmt = $db->prepare($insertSql);
    // TODO: Bind parameters
    // Bind student_id, name, email, and hashed password
    $insertStmt->bindParam(':student_id', $student_id);
    $insertStmt->bindParam(':name', $name);
    $insertStmt->bindParam(':email', $email);
    $insertStmt->bindParam(':password', $hashedPassword);
    // TODO: Execute the query
    $result = $insertStmt->execute();
    // TODO: Check if insert was successful
    // If yes, return success response with 201 status (Created)
    // If no, return error response with 500 status
    if ($result) {
        http_response_code(201); 
        echo json_encode([
            "success" => true,
            "message" => "Student created successfully"
        ]);
    } else {
        http_response_code(500); 
        echo json_encode([
            "success" => false,
            "message" => "Failed to create student"
        ]);
    }
}


/**
 * Function: Update an existing student
 * Method: PUT
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (to identify which student to update)
 *   - name: Updated student name (optional)
 *   - email: Updated student email (optional)
 */
function updateStudent($db, $data) {
    // TODO: Validate that student_id is provided
    // If not, return error response with 400 status
     $student_id = $data['student_id'] ?? null;
    if (!$student_id) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "student_id is required"
        ]);
        return;
    }
    // TODO: Check if student exists
    // Prepare and execute a SELECT query to find the student
    // If not found, return error response with 404 status
    $stmt = $db->prepare("SELECT * FROM students WHERE student_id = :student_id LIMIT 1");
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Student not found"
        ]);
        return;
    }
    // TODO: Build UPDATE query dynamically based on provided fields
    // Only update fields that are provided in the request
    $fields = [];
    $params = [':student_id' => $student_id];

    if (!empty($data['name'])) {
        $fields[] = "name = :name";
        $params[':name'] = trim($data['name']);
    }
    if (!empty($data['email'])) {
        $newEmail = trim($data['email']);
    // TODO: If email is being updated, check if new email already exists
    // Prepare and execute a SELECT query
    // Exclude the current student from the check
    // If duplicate found, return error response with 409 status
        $emailCheck = $db->prepare("SELECT * FROM students WHERE email = :email AND student_id != :student_id LIMIT 1");
        $emailCheck->bindParam(':email', $newEmail);
        $emailCheck->bindParam(':student_id', $student_id);
        $emailCheck->execute();
        if ($emailCheck->fetch(PDO::FETCH_ASSOC)) {
            http_response_code(409);
            echo json_encode([
                "success" => false,
                "message" => "Email already in use by another student"
            ]);
            return;
        }
        $fields[] = "email = :email";
        $params[':email'] = $newEmail;
    }

    if (empty($fields)) {
        echo json_encode([
            "success" => false,
            "message" => "No fields to update"
        ]);
        return;
    }

    $sql = "UPDATE students SET " . implode(", ", $fields) . " WHERE student_id = :student_id";
    $updateStmt = $db->prepare($sql);
    // TODO: Bind parameters dynamically
    // Bind only the parameters that are being updated
    foreach ($params as $key => $value) {
        $updateStmt->bindValue($key, $value);
    }
    // TODO: Execute the query
    $result = $updateStmt->execute();
    // TODO: Check if update was successful
    // If yes, return success response
    // If no, return error response with 500 status
    if ($result) {
        echo json_encode([
            "success" => true,
            "message" => "Student updated successfully"
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Failed to update student"
        ]);
    }
}


/**
 * Function: Delete a student
 * Method: DELETE
 * 
 * Query Parameters or JSON Body:
 *   - student_id: The student's university ID
 */
function deleteStudent($db, $studentId) {
    // TODO: Validate that student_id is provided
    // If not, return error response with 400 status
    if (!$studentId) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "student_id is required"
        ]);
        return;
    }
    // TODO: Check if student exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
    $stmt = $db->prepare("SELECT * FROM students WHERE student_id = :student_id LIMIT 1");
    $stmt->bindParam(':student_id', $studentId);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Student not found"
        ]);
        return;
    }
    // TODO: Prepare DELETE query
    $deleteSql = "DELETE FROM students WHERE student_id = :student_id";
    $deleteStmt = $db->prepare($deleteSql);
    // TODO: Bind the student_id parameter
    $deleteStmt->bindParam(':student_id', $studentId);
    // TODO: Execute the query
    $result = $deleteStmt->execute();
    // TODO: Check if delete was successful
    // If yes, return success response
    // If no, return error response with 500 status
    if ($result) {
        echo json_encode([
            "success" => true,
            "message" => "Student deleted successfully"
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Failed to delete student"
        ]);
    }
}


/**
 * Function: Change password
 * Method: POST with action=change_password
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (identifies whose password to change)
 *   - current_password: The student's current password
 *   - new_password: The new password to set
 */
function changePassword($db, $data) {
    // TODO: Validate required fields
    // Check if student_id, current_password, and new_password are provided
    // If any field is missing, return error response with 400 status
    $student_id = $data['student_id'] ?? null;
    $current_password = $data['current_password'] ?? null;
    $new_password = $data['new_password'] ?? null;

    if (!$student_id || !$current_password || !$new_password) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Missing required fields"
        ]);
        return;
    }
    // TODO: Validate new password strength
    // Check minimum length (at least 8 characters)
    // If validation fails, return error response with 400 status
    if (strlen($new_password) < 8) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "New password must be at least 8 characters long"
        ]);
        return;
    }
    // TODO: Retrieve current password hash from database
    // Prepare and execute SELECT query to get password
    $stmt = $db->prepare("SELECT password FROM students WHERE student_id = :student_id LIMIT 1");
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Student not found"
        ]);
        return;
    }
    // TODO: Verify current password
    // Use password_verify() to check if current_password matches the hash
    // If verification fails, return error response with 401 status (Unauthorized)
     if (!password_verify($current_password, $student['password'])) {
        http_response_code(401); // Unauthorized
        echo json_encode([
            "success" => false,
            "message" => "Current password is incorrect"
        ]);
        return;
    }
    // TODO: Hash the new password
    // Use password_hash() with PASSWORD_DEFAULT
    $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
    // TODO: Update password in database
    // Prepare UPDATE query
    $updateStmt = $db->prepare("UPDATE students SET password = :password WHERE student_id = :student_id");
    $updateStmt->bindParam(':password', $hashedPassword);
    $updateStmt->bindParam(':student_id', $student_id);
    // TODO: Bind parameters and execute
    $result = $updateStmt->execute();
    // TODO: Check if update was successful
    // If yes, return success response
    // If no, return error response with 500 status
    if ($result) {
        echo json_encode([
            "success" => true,
            "message" => "Password updated successfully"
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Failed to update password"
        ]);
    }
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Route the request based on HTTP method
    
    if ($method === 'GET') {
        // TODO: Check if student_id is provided in query parameters
        // If yes, call getStudentById()
        // If no, call getStudents() to get all students (with optional search/sort)
         if (isset($_GET['student_id'])) {
            $studentId = $_GET['student_id'];
            getStudentById($db, $studentId);
        } else {
            getStudents($db);
        }
    } elseif ($method === 'POST') {
        // TODO: Check if this is a change password request
        // Look for action=change_password in query parameters
        // If yes, call changePassword()
        // If no, call createStudent()
        if (isset($_GET['action']) && $_GET['action'] === 'change_password') {
            $data = json_decode(file_get_contents('php://input'), true);
            changePassword($db, $data);
        } else {
            $data = json_decode(file_get_contents('php://input'), true);
            createStudent($db, $data);
        }
    } elseif ($method === 'PUT') {
        // TODO: Call updateStudent()
        $data = json_decode(file_get_contents('php://input'), true);
        updateStudent($db, $data);
    } elseif ($method === 'DELETE') {
        // TODO: Get student_id from query parameter or request body
        // Call deleteStudent()
        $studentId = $_GET['student_id'] ?? null;
        if (!$studentId) {
            $data = json_decode(file_get_contents('php://input'), true);
            $studentId = $data['student_id'] ?? null;
        }
        deleteStudent($db, $studentId);
    } else {
        // TODO: Return error for unsupported methods
        // Set HTTP status to 405 (Method Not Allowed)
        // Return JSON error message
        http_response_code(405);
        echo json_encode([
            "success" => false,
            "message" => "Method Not Allowed"
        ]);
    }
    
} catch (PDOException $e) {
    // TODO: Handle database errors
    // Log the error message (optional)
    // Return generic error response with 500 status
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error"
    ]);
    
} catch (Exception $e) {
    // TODO: Handle general errors
    // Return error response with 500 status
    error_log("General error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Server error"
    ]);
}


// ============================================================================
// HELPER FUNCTIONS (Optional but Recommended)
// ============================================================================

/**
 * Helper function to send JSON response
 * 
 * @param mixed $data - Data to send
 * @param int $statusCode - HTTP status code
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
    http_response_code($statusCode);
    // TODO: Echo JSON encoded data
    header('Content-Type: application/json');
    echo json_encode($data);
    // TODO: Exit to prevent further execution
    exit;
}


/**
 * Helper function to validate email format
 * 
 * @param string $email - Email address to validate
 * @return bool - True if valid, false otherwise
 */
function validateEmail($email) {
    // TODO: Use filter_var with FILTER_VALIDATE_EMAIL
    // Return true if valid, false otherwise
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}


/**
 * Helper function to sanitize input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Trim whitespace
    $data = trim($data);
    // TODO: Strip HTML tags using strip_tags()
     $data = strip_tags($data);
    // TODO: Convert special characters using htmlspecialchars()
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    // Return sanitized data
    return $data;
}

?>
