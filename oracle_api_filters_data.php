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

// Retrieve parameters from the request
$selectedLocations = isset($_GET['locationValues']) ? json_decode($_GET['locationValues'], true) : [];
$selectedCategories = isset($_GET['categoryValues']) ? json_decode($_GET['categoryValues'], true) : [];
$selectedDegrees = isset($_GET['degreeValues']) ? json_decode($_GET['degreeValues'], true) : [];
$searchTerm = isset($_GET['searchTerm']) ? $_GET['searchTerm'] : '';



// Initialize SQL query for jobs
$sql = "SELECT * FROM $table_name_oracle WHERE 1=1";
$params = [];
$types = '';

if (!empty($selectedLocations)) {
    $locationsPlaceholder = implode(',', array_fill(0, count($selectedLocations), '?'));
    $sql .= " AND primaryLocation IN ($locationsPlaceholder)";
    $params = array_merge($params, $selectedLocations);
    $types .= str_repeat('s', count($selectedLocations));
}

if (!empty($selectedCategories)) {
    $categoriesPlaceholder = implode(',', array_fill(0, count($selectedCategories), '?'));
    $sql .= " AND category IN ($categoriesPlaceholder)";
    $params = array_merge($params, $selectedCategories);
    $types .= str_repeat('s', count($selectedCategories));
}

if (!empty($selectedDegrees)) {
    $degreesPlaceholder = implode(',', array_fill(0, count($selectedDegrees), '?'));
    $sql .= " AND studyLevel IN ($degreesPlaceholder)";
    $params = array_merge($params, $selectedDegrees);
    $types .= str_repeat('s', count($selectedDegrees));
}

$searchTerm = trim($searchTerm);
$searchTerm = preg_replace('/\s+/', ' ', $searchTerm);
if (!empty($searchTerm)) {
    // Prepare the SQL query to search across multiple columns
    $sql .= " AND (title LIKE ? OR corporateDescriptionStr LIKE ?  OR externalDescriptionStr LIKE ? OR primaryLocation LIKE ?)";
    
    // Add the search term parameters with wildcards for partial matching
    $searchParam = "%$searchTerm%";
    $params[] = $searchParam; // For title
    $params[] = $searchParam; // For corp. description
    $params[] = $searchParam; // For external. description
    $params[] = $searchParam; // For location
    
    // Append 's' for each parameter added to the types string
    $types .= 'ssss'; // Four 's' for four string parameters
}
// Prepare and execute the statement for jobs
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die(json_encode(["error" => "Prepare failed: " . $conn->error]));
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die(json_encode(["error" => "Query failed: " . $stmt->error]));
}


// Free result set
$result->free();

// Queries to get all unique categories, locations, and degree levels
$categoriesQuery = "SELECT DISTINCT category FROM $table_name_oracle";
$locationsQuery = "SELECT DISTINCT primaryLocation FROM $table_name_oracle";
$degreeLevelsQuery = "SELECT DISTINCT studyLevel FROM $table_name_oracle";

$categoriesResult = $conn->query($categoriesQuery);
$locationsResult = $conn->query($locationsQuery);
$degreeLevelsResult = $conn->query($degreeLevelsQuery);


if (!$categoriesResult || !$locationsResult || !$degreeLevelsResult) {
    die(json_encode(["error" => "Query failed: " . $conn->error]));
}

$categories = [];
$locations = [];
$degreeLevels = [];

while ($row = $categoriesResult->fetch_assoc()) {
    if (!empty($row['category'])) {
        $categories[] = $row['category'];
    }
}

while ($row = $locationsResult->fetch_assoc()) {
    if (!empty($row['primaryLocation'])) {
        $locations[] = $row['primaryLocation'];
    }
}

while ($row = $degreeLevelsResult->fetch_assoc()) {
    if (!empty($row['studyLevel'])) {
        $degreeLevels[] = $row['studyLevel'];
    }
}

// Free result sets
$categoriesResult->free();
$locationsResult->free();
$degreeLevelsResult->free();
$conn->close();

// Return success message
echo json_encode([
    "status" => "success",
    "from" => "oracle",
    "message" => "Data retrieved successfully",
    "categories" => $categories,
    "locations" => $locations,
    "degreeLevels" => $degreeLevels
]);
?>
