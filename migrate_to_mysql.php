<?php
require_once 'includes/functions.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header('HTTP/1.1 403 Forbidden');
        echo json_encode([
            'success' => false,
            'message' => 'Invalid CSRF token'
        ]);
        exit;
    }
    
    // Get MySQL connection data
    $host = isset($_POST['mysql_host']) ? $_POST['mysql_host'] : '';
    $dbname = isset($_POST['mysql_dbname']) ? $_POST['mysql_dbname'] : '';
    $username = isset($_POST['mysql_username']) ? $_POST['mysql_username'] : '';
    $password = isset($_POST['mysql_password']) ? $_POST['mysql_password'] : '';
    
    // Validate required fields
    if (empty($host) || empty($dbname) || empty($username)) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode([
            'success' => false,
            'message' => 'Host, database name, and username are required'
        ]);
        exit;
    }
    
    // Migrate data to MySQL
    $result = migrateToMySQL($host, $dbname, $username, $password);
    
    if ($result === true) {
        echo json_encode([
            'success' => true,
            'message' => 'Data successfully migrated to MySQL'
        ]);
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode([
            'success' => false,
            'message' => $result
        ]);
    }
    exit;
}

// If requested with GET, generate schema only
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['generate_schema'])) {
    $schemaFile = generateMySQLSchema();
    
    if ($schemaFile) {
        echo json_encode([
            'success' => true,
            'message' => 'MySQL schema generated successfully',
            'schema_file' => basename($schemaFile)
        ]);
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode([
            'success' => false,
            'message' => 'Failed to generate MySQL schema'
        ]);
    }
    exit;
}

// If not POST or valid GET, return error
header('HTTP/1.1 405 Method Not Allowed');
echo json_encode([
    'success' => false,
    'message' => 'Method not allowed'
]);
exit;
?>