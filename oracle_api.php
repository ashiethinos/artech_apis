<?php
include './config.php';

// Database credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = $database_name;

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

// Define SQL query to select all data from the table
$sql = "SELECT * FROM oracle_jobs";
$result = $conn->query($sql);

// Check for errors in query execution
if (!$result) {
    die(json_encode(["error" => "Query failed: " . $conn->error]));
}

// Fetch the results and store in an array
$jobs = [];
while ($row = $result->fetch_assoc()) {
    $jobs[] = $row;
}

// Free result set
$result->free();
$conn->close();

// Return success message
echo json_encode([
    "status"=>"success",
    "from"=>"oracle",
    "message" => "Data retrieved successfully",
    "jobs" => $jobs
]);
?>
