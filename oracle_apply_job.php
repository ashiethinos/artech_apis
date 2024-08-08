<?php 
include './config.php';

// Get POST data
$firstName = isset($_POST['firstName']) ? $_POST['firstName'] : '';
$lastName = isset($_POST['lastName']) ? $_POST['lastName'] : '';
$phone = isset($_POST['phone']) ? $_POST['phone'] : '';
$email = isset($_POST['email']) ? $_POST['email'] : '';
$jobId = isset($_POST['jobId']) ? $_POST['jobId'] : '';
$info = isset($_POST['info']) ? $_POST['info'] : '';

// Check if all fields are filled
if (empty($firstName) || empty($lastName) || empty($phone) || empty($email) || empty($jobId)) {
    echo json_encode(array("status" => "error", "message" => "Please fill in all fields."));
    exit();
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(array("status" => "error", "message" => "Invalid email address."));
    exit();
}

// Handle file upload
$file = isset($_FILES['file']) ? $_FILES['file'] : null;
$fileName = '';

if ($file && $file['error'] === UPLOAD_ERR_OK) {
    $fileTmpName = $file['tmp_name'];
    $fileName = basename($file['name']);
    $fileType = mime_content_type($fileTmpName); // Get the MIME type of the file
    $fileSize = $file['size']; // File size in bytes
    $uploadDir = './files/';
    $targetPath = $uploadDir . $fileName;

    // Validate file type
    $allowedTypes = array('image/jpeg', 'image/png', 'image/svg+xml', 'application/pdf');
    if (!in_array($fileType, $allowedTypes)) {
        echo json_encode(array("status" => "error", "message" => "Invalid file type. Allowed types: JPG, PNG, SVG, PDF."));
        exit();
    }

    // Validate file size (e.g., limit to 5MB)
    $maxFileSize = 5 * 1024 * 1024; // 5MB in bytes
    if ($fileSize > $maxFileSize) {
        echo json_encode(array("status" => "error", "message" => "File size exceeds the maximum limit of 5MB."));
        exit();
    }

    // Ensure the upload directory exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Move uploaded file
    if (!move_uploaded_file($fileTmpName, $targetPath)) {
        echo json_encode(array("status" => "error", "message" => "Failed to upload file."));
        exit();
    }
} else {
    // No file was uploaded or there was an error with the file upload
    if (isset($file) && $file['error'] !== UPLOAD_ERR_NO_FILE) {
        echo json_encode(array("status" => "error", "message" => "File upload error: " . $file['error']));
        exit();
    } else {
        // If no file was uploaded and no error occurred
        echo json_encode(array("status" => "error", "message" => "No file uploaded. Please upload a file."));
        exit();
    }
    
}

// Create database connection
$conn = new mysqli($servername, $username, $password, $database_name);

if ($conn->connect_error) {
    die(json_encode(array("status" => "error", "message" => "Connection failed: " . $conn->connect_error)));
}

// Prepare SQL statement
$sql = "INSERT INTO $oracle_job_applications_table (jobId, firstName, lastName, phone, email, info, file) VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("sssssss", $jobId, $firstName, $lastName, $phone, $email, $info, $fileName);
    if ($stmt->execute()) {
        echo json_encode(array("status" => "success", "message" => "Application submitted successfully."));
    } else {
        echo json_encode(array("status" => "error", "message" => "Error: " . $stmt->error));
    }
    $stmt->close();
} else {
    echo json_encode(array("status" => "error", "message" => "Failed to prepare the SQL statement."));
}

$conn->close();
?>
