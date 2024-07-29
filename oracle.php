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
            $job['Id'] ?? 'N/A',
            $job['Title'] ?? 'N/A',
            $job['PostedDate'] ?? 'N/A',
            $job['PostingEndDate'] ?? 'N/A',
            $job['Language'] ?? 'N/A',
            $job['PrimaryLocationCountry'] ?? 'N/A',
            $job['GeographyId'] ?? 'N/A',
            $job['HotJobFlag'] ? 'Yes' : 'No',
            $job['WorkplaceTypeCode'] ?? 'N/A',
            $job['JobFamily'] ?? 'N/A',
            $job['JobFunction'] ?? 'N/A',
            $job['WorkerType'] ?? 'N/A',
            $job['ContractType'] ?? 'N/A',
            $job['ManagerLevel'] ?? 'N/A',
            $job['JobSchedule'] ?? 'N/A',
            $job['JobShift'] ?? 'N/A',
            $job['JobType'] ?? 'N/A',
            $job['StudyLevel'] ?? 'N/A',
            $job['DomesticTravelRequired'] ?? 'N/A',
            $job['InternationalTravelRequired'] ?? 'N/A',
            $job['WorkDurationYears'] ?? 'N/A',
            $job['WorkDurationMonths'] ?? 'N/A',
            $job['WorkHours'] ?? 'N/A',
            $job['WorkDays'] ?? 'N/A',
            $job['LegalEmployer'] ?? 'N/A',
            $job['BusinessUnit'] ?? 'N/A',
            $job['Department'] ?? 'N/A',
            $job['Organization'] ?? 'N/A',
            $job['MediaThumbURL'] ?? 'N/A',
            $job['ShortDescriptionStr'] ?? 'N/A',
            $job['PrimaryLocation'] ?? 'N/A',
            $job['Distance'] ?? 'N/A',
            $job['TrendingFlag'] ? 'Yes' : 'No',
            $job['BeFirstToApplyFlag'] ? 'Yes' : 'No',
            $job['Relevancy'] ?? 'N/A',
            $job['WorkplaceType'] ?? 'N/A',
            $job['ExternalQualificationsStr'] ?? 'N/A',
            $job['ExternalResponsibilitiesStr'] ?? 'N/A',
            json_encode($job['secondaryLocations']) // Encode as JSON for storage
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
