<?php

include './config.php';
// Database credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = $database_name;

// External API URL
$api_url = 'https://api.jobdiva.com/api/bi/PortalJobsList?portalID=-1';

// Initialize cURL session
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Accept: application/json',
    'Authorization: ' . $jobdiva_auth_token,
    'X-CSRF-TOKEN: 13edf39f-7f18-4b82-86c3-904c2fb2dd89'
)); 

// Execute cURL session
$api_response = curl_exec($ch);

$dataIds = json_decode($api_response, true);


$jobIds = [];

foreach (array_slice($dataIds['data'], 1) as $single_data) {
    $jobIds[] = $single_data[0];
}

curl_close($ch); // Close the initial cURL session

// Connect to the database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

// Function to add missing columns to the table
function addMissingColumns($conn, $table_name, $columns) {
    $existing_columns = [];
    $result = $conn->query("SHOW COLUMNS FROM `$table_name`");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $existing_columns[] = $row['Field'];
        }
    }

    foreach ($columns as $column) {
        if (!in_array($column, $existing_columns)) {
            $sql = "ALTER TABLE `$table_name` ADD COLUMN `$column` TEXT";
            if (!$conn->query($sql)) {
                return "Error adding column `$column`: " . $conn->error;
            }
        }
    }
    return true;
}

// Define table name and columns
$table_name = $table_name_jobdiva;
$columns = [
    "id", "dateIssued", "dateUpdated", "dateUserFieldUpdated", "dateStatusUpdated",
    "jobStatus", "customerId", "companyId", "address1", "address2", "city", "state",
    "zipCode", "country", "priority", "division", "refNo", "jobDivaNo", "startDate",
    "endDate", "positions", "fills", "maxAllowedSubmittals", "billRateMin",
    "billRateMax", "billRatePer", "payRateMin", "payRateMax", "payRatePer",
    "positionType", "skills", "jobTitle", "jobDescription", "remarks", "submittalInstruction",
    "postToPortal", "postingTitle", "postingDate", "postingDescription", "criteriaDegree",
    "jobCatalogId", "catalogCompanyId", "catalogTitle", "catalogRefNo", "catalogName",
    "catalogActive", "catalogEffectiveDate", "catalogExpirationDate", "catalogCategory",
    "catalogBillRateLow", "catalogBillRateHigh", "catalogBillRatePer", "catalogPayRateLow",
    "catalogPayRateHigh", "catalogPayRatePer", "positionRefNo", "preventLowerPay",
    "preventHigherBill", "catalogNotes", "ot", "references", "travel", "drugTest",
    "backgroundCheck", "securityClearance", "onsiteFlexibility", "remotePercentage",
    "fee", "feeType", "jobCategory", "postingCity", "postingState", "postingZipCode",
    "postingCountry", "requiredCountry", "requiredState", "requiredAreaCodes",
    "requiredZipCode", "requiredWithin", "requiredPayRangeFrom", "requiredPayRangeTo",
    "requiredPayRangePer", "requiredMajor", "requiredDegree"
];

// Add missing columns if necessary
$addColumnsResult = addMissingColumns($conn, $table_name, $columns);

if ($addColumnsResult !== true) {
    die(json_encode(["error" => $addColumnsResult]));
}

// Prepare statement for inserting data
$stmt = $conn->prepare("INSERT INTO `$table_name` (`" . implode("`, `", $columns) . "`) VALUES (" . rtrim(str_repeat("?, ", count($columns)), ", ") . ")");

if (!$stmt) {
    die(json_encode(["error" => "Prepare statement failed: " . $conn->error]));
}


// Loop through each job ID
foreach ($jobIds as $jobId) {


    $check_sql = "SELECT 1 FROM `$table_name` WHERE `ID` = ?";
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

    // Initialize cURL session for each job ID
    $ch2 = curl_init();
    curl_setopt($ch2, CURLOPT_URL, $api_url_for_job);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Authorization: ' . $jobdiva_auth_token,
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

  
    if ($data['data'] == null) {
        continue; // Skip if no data available
    }

    // Bind parameters and insert data
    foreach (array_slice($data['data'], 1) as $job) { // Skip the first element which contains column names
        $params = array_values($job); // Extract values from associative array
        if (!$stmt->bind_param(str_repeat("s", count($params)), ...$params)) {
            die(json_encode(["error" => "Binding parameters failed: " . $stmt->error]));
        }

        // Execute insert statement
        if (!$stmt->execute()) {
            die(json_encode(["error" => "Execute failed: " . $stmt->error]));
        }
    }
}

// Close statement and database connection
$stmt->close();
$conn->close();

// Return success message
echo json_encode(["message" => "Table created/updated and All data inserted successfully"]);
?>
