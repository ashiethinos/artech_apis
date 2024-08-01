<?php
include './config.php';

// Database credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = $database_name;

// External API URL
$api_url = 'https://fa-erqf-saasfaprod1.fa.ocs.oraclecloud.com/hcmRestApi/resources/latest/recruitingCEJobRequisitions?onlyData=true&expand=requisitionList.secondaryLocations,flexFieldsFacet.values&finder=findReqs;siteNumber=CX_1,facetsList=LOCATIONS%3BWORK_LOCATIONS%3BWORKPLACE_TYPES%3BTITLES%3BCATEGORIES%3BORGANIZATIONS%3BPOSTING_DATES%3BFLEX_FIELDS,limit=25,sortBy=POSTING_DATES_DESC,offset=0';

// Initialize cURL session
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Accept: application/json',
    'X-CSRF-TOKEN: 13edf39f-7f18-4b82-86c3-904c2fb2dd89'
));

// Execute cURL session
$api_response = curl_exec($ch);
curl_close($ch); // Close the cURL session

$api_response_jobs_data = json_decode($api_response, true)['items'][0]['requisitionList'];



// Connect to the database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

// Define table name and columns
$table_name = $table_name_oracle;

// Function to add missing columns to the table start -------------------------------------------
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

$columns = [
    'id' => 'VARCHAR(255)', // Added ID column
    'title' => 'VARCHAR(255)',
    'postedDate' => 'DATE',
    'postingEndDate' => 'DATE',
    'language' => 'VARCHAR(10)',
    'primaryLocationCountry' => 'VARCHAR(10)',
    'geographyId' => 'BIGINT',
    'hotJobFlag' => 'BOOLEAN',
    'workplaceTypeCode' => 'VARCHAR(50)',
    'jobFamily' => 'VARCHAR(255)',
    'jobFunction' => 'VARCHAR(255)',
    'workerType' => 'VARCHAR(255)',
    'contractType' => 'VARCHAR(255)',
    'managerLevel' => 'VARCHAR(255)',
    'jobSchedule' => 'VARCHAR(255)',
    'jobShift' => 'VARCHAR(255)',
    'jobType' => 'VARCHAR(255)',
    'studyLevel' => 'VARCHAR(255)',
    'domesticTravelRequired' => 'VARCHAR(255)',
    'internationalTravelRequired' => 'VARCHAR(255)',
    'workDurationYears' => 'INT',
    'workDurationMonths' => 'INT',
    'workHours' => 'VARCHAR(255)',
    'workDays' => 'VARCHAR(255)',
    'legalEmployer' => 'VARCHAR(255)',
    'businessUnit' => 'VARCHAR(255)',
    'department' => 'VARCHAR(255)',
    'organization' => 'VARCHAR(255)',
    'mediaThumbURL' => 'VARCHAR(255)',
    'shortDescriptionStr' => 'TEXT',
    'primaryLocation' => 'VARCHAR(255)',
    'distance' => 'FLOAT',
    'trendingFlag' => 'BOOLEAN',
    'beFirstToApplyFlag' => 'BOOLEAN',
    'relevancy' => 'INT',
    'workplaceType' => 'VARCHAR(255)',
    'externalQualificationsStr' => 'TEXT',
    'externalResponsibilitiesStr' => 'TEXT',
    'secondaryLocations' => 'TEXT' // This will be JSON encoded
];

// Add missing columns if necessary
$addColumnsResult = addMissingColumns($conn, $table_name, $columns);

if ($addColumnsResult !== true) {
    die(json_encode(["error" => $addColumnsResult]));
}
// Function to add missing columns to the table end ----------------------------------------------



// Prepare statement for inserting data
$columnsList = array_keys($columns);
$placeholders = rtrim(str_repeat("?, ", count($columns)), ", ");

$sql = "INSERT INTO `$table_name` (`" . implode("`, `", $columnsList) . "`) VALUES ($placeholders)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die(json_encode(["error" => "Prepare statement failed: " . $conn->error]));
}

// Prepare statement to check for existing job IDs
$check_sql = "SELECT COUNT(*) FROM `$table_name` WHERE `id` = ?";

$check_stmt = $conn->prepare($check_sql);

if (!$check_stmt) {
    die(json_encode(["error" => "Prepare check statement failed: " . $conn->error]));
}

// Loop through each job and insert if not already present
foreach ($api_response_jobs_data as $job) {
    $jobId = $job['Id'];
    
    // Check if the job already exists
    $check_stmt->bind_param('s', $jobId);
    $check_stmt->execute();
    $check_stmt->bind_result($exists);
    $check_stmt->fetch();
    $check_stmt->free_result(); // Free previous result set
    
    if ($exists == 0) {
        // Prepare parameters for insertion
        $params = [
            $jobId,
            !isset($job['Title']) ? null : $job['Title'],
            !isset($job['PostedDate']) ? null : $job['PostedDate'],
            !isset($job['PostingEndDate']) ? null : $job['PostingEndDate'],
            !isset($job['Language']) ? null : $job['Language'],
            !isset($job['PrimaryLocationCountry']) ? null : $job['PrimaryLocationCountry'],
            !isset($job['GeographyId']) ? null : $job['GeographyId'],
            !isset($job['HotJobFlag']) ? null : ($job['HotJobFlag'] ? 'Yes' : 'No'),
            !isset($job['WorkplaceTypeCode']) ? null : $job['WorkplaceTypeCode'],
            !isset($job['JobFamily']) ? null : $job['JobFamily'],
            !isset($job['JobFunction']) ? null : $job['JobFunction'],
            !isset($job['WorkerType']) ? null : $job['WorkerType'],
            !isset($job['ContractType']) ? null : $job['ContractType'],
            !isset($job['ManagerLevel']) ? null : $job['ManagerLevel'],
            !isset($job['JobSchedule']) ? null : $job['JobSchedule'],
            !isset($job['JobShift']) ? null : $job['JobShift'],
            !isset($job['JobType']) ? null : $job['JobType'],
            !isset($job['StudyLevel']) ? null : $job['StudyLevel'],
            !isset($job['DomesticTravelRequired']) ? null : $job['DomesticTravelRequired'],
            !isset($job['InternationalTravelRequired']) ? null : $job['InternationalTravelRequired'],
            !isset($job['WorkDurationYears']) ? null : $job['WorkDurationYears'],
            !isset($job['WorkDurationMonths']) ? null : $job['WorkDurationMonths'],
            !isset($job['WorkHours']) ? null : $job['WorkHours'],
            !isset($job['WorkDays']) ? null : $job['WorkDays'],
            !isset($job['LegalEmployer']) ? null : $job['LegalEmployer'],
            !isset($job['BusinessUnit']) ? null : $job['BusinessUnit'],
            !isset($job['Department']) ? null : $job['Department'],
            !isset($job['Organization']) ? null : $job['Organization'],
            !isset($job['MediaThumbURL']) ? null : $job['MediaThumbURL'],
            !isset($job['ShortDescriptionStr']) ? null : $job['ShortDescriptionStr'],
            !isset($job['PrimaryLocation']) ? null : $job['PrimaryLocation'],
            !isset($job['Distance']) ? null : $job['Distance'],
            !isset($job['TrendingFlag']) ? null : ($job['TrendingFlag'] ? 'Yes' : 'No'),
            !isset($job['BeFirstToApplyFlag']) ? null : ($job['BeFirstToApplyFlag'] ? 'Yes' : 'No'),
            !isset($job['Relevancy']) ? null : $job['Relevancy'],
            !isset($job['WorkplaceType']) ? null : $job['WorkplaceType'],
            !isset($job['ExternalQualificationsStr']) ? null : $job['ExternalQualificationsStr'],
            !isset($job['ExternalResponsibilitiesStr']) ? null : $job['ExternalResponsibilitiesStr'],
            !isset($job['SecondaryLocations']) || empty($job['SecondaryLocations']) ? null : json_encode($job['SecondaryLocations'])
        ];
        
        // Bind parameters and insert data
        $types = str_repeat("s", count($params));
        $stmt->bind_param($types, ...$params);
        
        if (!$stmt->execute()) {
            die(json_encode(["error" => "Execute failed: " . $stmt->error]));
        }
    }
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

// Close statements and database connection
$stmt->close();
$check_stmt->close();
$conn->close();



// Return success message
echo json_encode([
    "status"=>"success",
    "from"=>"oracle",
    "message" => "Data retrieved successfully",
    "jobs" => $jobs
]);
?>
