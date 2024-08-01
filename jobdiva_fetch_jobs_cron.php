<?php

include './config.php';
// Database credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = $database_name;

$authentication_url = "https://api.jobdiva.com/api/authenticate?clientid=" . urlencode($jobdiva_clientId) . "&username=" . urlencode($jobdiva_username) . "&password=" . urlencode($jobdiva_password);


// Initialize cURL authentication session
$ch_auth = curl_init();
curl_setopt($ch_auth, CURLOPT_URL, $authentication_url);
curl_setopt($ch_auth, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch_auth, CURLOPT_HTTPHEADER, array(
    'accept: */*',
    'X-CSRF-TOKEN: fb74f218-389d-4976-aecc-a3fde1302600'
)); 

// Execute cURL session
$auth_token = curl_exec($ch_auth);

// External API URL
$api_url = 'https://api.jobdiva.com/api/bi/PortalJobsList?portalID=-1';

// Initialize cURL session
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Accept: application/json',
    'Authorization: ' .$auth_token,
    'X-CSRF-TOKEN: 13edf39f-7f18-4b82-86c3-904c2fb2dd89'
)); 

// Execute cURL session
$api_response = curl_exec($ch);

$dataIds = json_decode($api_response, true);




if(isset($dataIds['status']) == 401){
    var_dump($dataIds['message']);
    die;
}

$jobIds = [];

foreach (array_slice($dataIds['data'], 1) as $single_data) {
    $jobIds[] = $single_data[0];
}

// print_r($jobIds);

curl_close($ch); // Close the initial cURL session

// Connect to the database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

// Function to add missing columns to the table start ---------------------

function addMissingColumns($conn, $table_name, $columns) {
    $existing_columns = [];
    $result = $conn->query("SHOW COLUMNS FROM `$table_name`");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $existing_columns[$row['Field']] = $row['Type'];
        }
    }

    $errors = [];
    foreach ($columns as $column => $type) {
        if (!array_key_exists($column, $existing_columns)) {
            $sql = "ALTER TABLE `$table_name` ADD COLUMN `$column` $type";
            if (!$conn->query($sql)) {
                $errors[] = "Error adding column `$column` with type `$type`: " . $conn->error;
            }
        }
    }
    
    return empty($errors) ? true : $errors;
}

// Define table name and columns
$table_name = $table_name_jobdiva;
$columns = [
    "dateIssued" => "DATETIME",
    "dateUpdated" => "DATETIME",
    "dateUserFieldUpdated" => "DATETIME",
    "dateStatusUpdated" => "DATETIME",
    "jobStatus" => "VARCHAR(50)",
    "customerId" => "INT",
    "companyId" => "INT",
    "address1" => "VARCHAR(255)",
    "address2" => "VARCHAR(255)",
    "city" => "VARCHAR(100)",
    "state" => "VARCHAR(100)",
    "zipCode" => "VARCHAR(20)",
    "country" => "VARCHAR(100)",
    "priority" => "VARCHAR(50)",
    "division" => "VARCHAR(100)",
    "refNo" => "VARCHAR(50)",
    "jobDivaNo" => "VARCHAR(50)",
    "startDate" => "DATE",
    "endDate" => "DATE",
    "positions" => "INT",
    "fills" => "INT",
    "maxAllowedSubmittals" => "INT",
    "billRateMin" => "DECIMAL(10, 2)",
    "billRateMax" => "DECIMAL(10, 2)",
    "billRatePer" => "VARCHAR(50)",
    "payRateMin" => "DECIMAL(10, 2)",
    "payRateMax" => "DECIMAL(10, 2)",
    "payRatePer" => "VARCHAR(50)",
    "positionType" => "VARCHAR(50)",
    "skills" => "TEXT",
    "jobTitle" => "VARCHAR(255)",
    "jobDescription" => "TEXT",
    "remarks" => "TEXT",
    "submittalInstruction" => "TEXT",
    "postToPortal" => "BOOLEAN",
    "postingTitle" => "VARCHAR(255)",
    "postingDate" => "DATE",
    "postingDescription" => "TEXT",
    "criteriaDegree" => "VARCHAR(50)",
    "jobCatalogId" => "INT",
    "catalogCompanyId" => "INT",
    "catalogTitle" => "VARCHAR(255)",
    "catalogRefNo" => "VARCHAR(50)",
    "catalogName" => "VARCHAR(255)",
    "catalogActive" => "BOOLEAN",
    "catalogEffectiveDate" => "DATE",
    "catalogExpirationDate" => "DATE",
    "catalogCategory" => "VARCHAR(100)",
    "catalogBillRateLow" => "DECIMAL(10, 2)",
    "catalogBillRateHigh" => "DECIMAL(10, 2)",
    "catalogBillRatePer" => "VARCHAR(50)",
    "catalogPayRateLow" => "DECIMAL(10, 2)",
    "catalogPayRateHigh" => "DECIMAL(10, 2)",
    "catalogPayRatePer" => "VARCHAR(50)",
    "positionRefNo" => "VARCHAR(50)",
    "preventLowerPay" => "BOOLEAN",
    "preventHigherBill" => "BOOLEAN",
    "catalogNotes" => "TEXT",
    "ot" => "BOOLEAN",
    "references" => "TEXT",
    "travel" => "BOOLEAN",
    "drugTest" => "BOOLEAN",
    "backgroundCheck" => "BOOLEAN",
    "securityClearance" => "VARCHAR(100)",
    "onsiteFlexibility" => "VARCHAR(50)",
    "remotePercentage" => "INT",
    "fee" => "DECIMAL(10, 2)",
    "feeType" => "VARCHAR(50)",
    "jobCategory" => "VARCHAR(100)",
    "postingCity" => "VARCHAR(100)",
    "postingState" => "VARCHAR(100)",
    "postingZipCode" => "VARCHAR(20)",
    "postingCountry" => "VARCHAR(100)",
    "requiredCountry" => "VARCHAR(100)",
    "requiredState" => "VARCHAR(100)",
    "requiredAreaCodes" => "VARCHAR(100)",
    "requiredZipCode" => "VARCHAR(20)",
    "requiredWithin" => "INT",
    "requiredPayRangeFrom" => "DECIMAL(10, 2)",
    "requiredPayRangeTo" => "DECIMAL(10, 2)",
    "requiredPayRangePer" => "VARCHAR(50)",
    "requiredMajor" => "VARCHAR(100)",
    "requiredDegree" => "VARCHAR(100)"
];


// Add missing columns if necessary
$addColumnsResult = addMissingColumns($conn, $table_name, $columns);

if ($addColumnsResult !== true) {
    die(json_encode(["error" => $addColumnsResult]));
}
// Function to add missing columns to the table end ---------------------


// Prepare statement for inserting data
$columnsList = array_keys($columns);
$placeholders = rtrim(str_repeat("?, ", count($columns)), ", ");

$sql = "INSERT INTO `$table_name` (`" . implode("`, `", $columnsList) . "`) VALUES ($placeholders)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die(json_encode(["error" => "Prepare statement failed: " . $conn->error]));
}


// Loop through each job ID
foreach ($jobIds as $jobId) {
$check_sql = "SELECT 1 FROM `$table_name` WHERE `id` = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("s", $jobId);
$check_stmt->execute();
$check_stmt->store_result();

if ($check_stmt->num_rows > 0) {
    $check_stmt->close();
    continue; // Skip if job ID already exists
}

$check_stmt->close();

$api_url_for_job = 'https://api.jobdiva.com/api/bi/JobsDetail?jobIds=' . urlencode($jobId);

echo "id for job is $jobId.";

// Initialize cURL session for each job ID
$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, $api_url_for_job);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
    'Accept: application/json',
    'Authorization: ' . $auth_token,
    'X-CSRF-TOKEN: 13edf39f-7f18-4b82-86c3-904c2fb2dd89'
));

// Execute cURL session
$api_response_for_job = curl_exec($ch2);

// Check for errors in cURL execution
if ($api_response_for_job === false) {
    curl_close($ch2);
    die(json_encode(["error" => "cURL error: " . curl_error($ch2)]));
}

// Close cURL session
curl_close($ch2);

// Decode JSON response
$data = json_decode($api_response_for_job, true);

if (!isset($data['data'])) {
    continue; // Skip if no data
}

// Prepare the statement for inserting data
$columnsList = array_keys($columns);
$placeholders = rtrim(str_repeat("?, ", count($columnsList)), ", ");
$sql = "INSERT INTO `$table_name` (`" . implode("`, `", $columnsList) . "`) VALUES ($placeholders)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die(json_encode(["error" => "Prepare statement failed: " . $conn->error]));
}

// Bind parameters and insert data
foreach (array_slice($data['data'], 1) as $job) { // Skip the first element if it contains column names
    $params = array_values($job);
    $param_types = str_repeat("s", count($params)); // Adjust if different types are used
    
    if (!$stmt->bind_param($param_types, ...$params)) {
        die(json_encode(["error" => "Binding parameters failed: " . $stmt->error]));
    }
    
    if (!$stmt->execute()) {
        die(json_encode(["error" => "Execute failed: " . $stmt->error]));
    }
}


}
// Close statement and database connection
$stmt->close();
$conn->close();

// Return success message
echo json_encode([
    "status"=>"success",
    "message" => "Jobs fetched and saved in database successfully from jobdiva",
]);
?>
