<?php
include './config.php';

// Database credentials

$dbname = $database_name;

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

$jobId = $_GET['jobId'];

// Initialize SQL query for jobs
$sql = "SELECT * FROM $table_name_oracle WHERE id=?";

// Prepare the SQL statement
$stmt = $conn->prepare($sql);

// Bind the parameters
$stmt->bind_param("i", $jobId);

// Execute the query
$stmt->execute();

// Get the result
$result = $stmt->get_result();

// Fetch the job data
$job = $result->fetch_assoc();

// Close the statement and connection
$stmt->close();
$conn->close();

// Return success message
echo json_encode([
    "status" => "success",
    "from" => "oracle",
    "message" => "Data retrieved successfully",
    "job" => $job
]);
?>
