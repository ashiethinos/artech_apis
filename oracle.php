<style>
    table {
        width: 100%;
        border-collapse: collapse;
    }
    th, td {
        border: 1px solid #ddd;
        padding: 8px;
    }
    th {
        background-color: #f2f2f2;
        text-align: left;
    }
</style>

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

var_dump($api_response_jobs_data);
die;

// Display data in a table

// Connect to the database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

// Define table name and columns
$table_name = $table_name_oracle;
$columns = [
    "id", "title", "postedDate", "postingEndDate", "language", "primaryLocationCountry",
    "geographyId", "hotJobFlag", "workplaceTypeCode", "jobFamily", "jobFunction",
    "workerType", "contractType", "managerLevel", "jobSchedule", "jobShift", "jobType",
    "studyLevel", "domesticTravelRequired", "internationalTravelRequired",
    "workDurationYears", "workDurationMonths", "workHours", "workDays", "legalEmployer",
    "businessUnit", "department", "organization", "mediaThumbUrl", "shortDescriptionStr",
    "primaryLocation", "distance", "trendingFlag", "beFirstToApplyFlag", "relevancy",
    "workplaceType", "externalQualificationsStr", "externalResponsibilitiesStr",
    "secondaryLocations"
];

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
            !isset($job['Id']) ? null : $job['Id'],
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
            !isset($job['TrendingFlag']) ? null : $job['TrendingFlag'],
            !isset($job['BeFirstToApplyFlag']) ? null : $job['BeFirstToApplyFlag'],
            !isset($job['Relevancy']) ? null : $job['Relevancy'],
            !isset($job['WorkplaceType']) ? null : $job['WorkplaceType'],
            !isset($job['ExternalQualificationsStr']) ? null : $job['ExternalQualificationsStr'],
            !isset($job['ExternalResponsibilitiesStr']) ? null : $job['ExternalResponsibilitiesStr'],
            !isset($job['secondaryLocations']) || empty($job['secondaryLocations']) ? null : json_encode($job['secondaryLocations'])
        ];
        
        // Bind parameters and insert data
        $stmt->bind_param(str_repeat("s", count($params)), ...$params);
        
        if (!$stmt->execute()) {
            die(json_encode(["error" => "Execute failed: " . $stmt->error]));
        }
    }
}

// Close statements and database connection
$stmt->close();
$check_stmt->close();
$conn->close();

// Return success message
echo json_encode(["message" => "Table created/updated and all new data inserted successfully"]);
?>
