<?php
include './config.php';
set_time_limit(300);
$start_time = microtime(true);
// Database credentials

$dbname = $database_name;

// External API URL
$api_url = 'https://fa-erqf-test-saasfaprod1.fa.ocs.oraclecloud.com/hcmRestApi/resources/latest/recruitingCEJobRequisitions?onlyData=true&expand=requisitionList.secondaryLocations,flexFieldsFacet.values,requisitionList.requisitionFlexFields&finder=findReqs;siteNumber=CX_1,facetsList=LOCATIONS%3BWORK_LOCATIONS%3BWORKPLACE_TYPES%3BTITLES%3BCATEGORIES%3BORGANIZATIONS%3BPOSTING_DATES%3BFLEX_FIELDS,limit=1000,sortBy=POSTING_DATES_DESC';

// Initialize cURL session
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Authorization: Basic ' . base64_encode('OIC_INT_USER:Artech@12345'),
));

// Execute cURL session
$api_response = curl_exec($ch);
if (curl_errno($ch)) {
    die(json_encode(["error" => "cURL error: " . curl_error($ch)]));
}
curl_close($ch); // Close the cURL session

$api_response_jobs_data = json_decode($api_response, true)['items'][0]['requisitionList'];

// Check for API response errors
if (json_last_error() !== JSON_ERROR_NONE) {
    die(json_encode(["error" => "JSON decode error: " . json_last_error_msg()]));
}

// Connect to the database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

// Define table name and columns
$table_name = $table_name_oracle;

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
    'id' => 'VARCHAR(255)',
    'title' => 'VARCHAR(255)',
    'postedDate' => 'DATE',
    'postingEndDate' => 'DATETIME',
    'language' => 'VARCHAR(50)',
    'primaryLocationCountry' => 'VARCHAR(50)',
    'geographyId' => 'BIGINT',
    'hotJobFlag' => 'BOOLEAN',
    'workplaceTypeCode' => 'VARCHAR(255)',
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
    // 'workHours' => 'VARCHAR(255)',
    // 'workDays' => 'VARCHAR(255)',
    // 'department' => 'VARCHAR(255)',
    // 'organization' => 'VARCHAR(255)',
    // 'mediaThumbUrl' => 'VARCHAR(255)',
    'shortDescriptionStr' => 'TEXT',
    'primaryLocation' => 'VARCHAR(255)',
    'distance' => 'FLOAT',
    'trendingFlag' => 'BOOLEAN',
    'beFirstToApplyFlag' => 'BOOLEAN',
    'relevancy' => 'INT',
    'workplaceType' => 'VARCHAR(255)',
    'externalQualificationsStr' => 'TEXT',
    'externalResponsibilitiesStr' => 'TEXT',
    'secondaryLocations' => 'TEXT',
    // Add extra columns here
    'category' => 'VARCHAR(255)',
    'requisitionType' => 'VARCHAR(255)',
    'jobGrade' => 'VARCHAR(255)',
    'requisitionId' => 'VARCHAR(255)',
    'externalPostedStartDate' => 'DATETIME',
    'jobLevel' => 'VARCHAR(255)',
    'externalContactName' => 'VARCHAR(255)',
    'externalContactEmail' => 'VARCHAR(255)',
    'externalPostedEndDate' => 'DATETIME',
    'jobFamilyId' => 'BIGINT',
    'geographyNodeId' => 'BIGINT',
    'externalDescriptionStr' => 'TEXT',
    'corporateDescriptionStr' => 'TEXT',
    'organizationDescriptionStr' => 'TEXT',
    'contentLocale' => 'VARCHAR(50)',
    'objectVerNumberProfile' => 'VARCHAR(50)',
    'applyWhenNotPostedFlag' => 'BOOLEAN',
    'internalQualificationsStr' => 'TEXT',
    'internalResponsibilitiesStr' => 'TEXT',
    // 'JobFunctionCode' => 'VARCHAR(255)',
    // 'OtherRequisitionTitle' => 'VARCHAR(255)',
    // 'NumberOfOpenings' => 'INT',
    // 'HiringManager' => 'VARCHAR(255)',
    // 'LegalEmployer' => 'VARCHAR(255)',
    // 'BusinessUnit' => 'VARCHAR(255)',
    // 'WorkMonths' => 'INT',
    // 'WorkYears' => 'INT',
    // 'media' => 'VARCHAR(255)',
    // 'workLocation' => 'VARCHAR(255)',
    // 'otherWorkLocations' => 'TEXT',
    'requisitionFlexFields' => 'TEXT',
    // 'primaryLocationCoordinates' => 'VARCHAR(255)',
    'skills' => 'TEXT'
];

// Add missing columns if necessary
$addColumnsResult = addMissingColumns($conn, $table_name, $columns);

if ($addColumnsResult !== true) {
    die(json_encode(["error" => $addColumnsResult]));
}

// Prepare statement for inserting data
$columnsList = array_keys($columns);
$placeholders = rtrim(str_repeat("?, ", count($columns)), ", ");
$types = str_repeat('s', count($columns)); // Assumes all columns are strings


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
    $check_stmt->free_result();



    if ($exists == 0) {

        $api_url_for_single_job = 'https://fa-erqf-test-saasfaprod1.fa.ocs.oraclecloud.com/hcmRestApi/resources/latest/recruitingCEJobRequisitionDetails?expand=all&onlyData=true&finder=ById;Id='. urlencode($jobId).',siteNumber=CX_1';

            // Initialize cURL session
        $ch_single = curl_init();
        curl_setopt($ch_single, CURLOPT_URL, $api_url_for_single_job);
        curl_setopt($ch_single, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch_single, CURLOPT_HTTPHEADER, array(
            'Authorization: Basic ' . base64_encode('OIC_INT_USER:Artech@12345'),
        ));

        // Execute cURL session
        $api_response_for_single_job = curl_exec($ch_single);
        $api_response_for_single_job_json = json_decode($api_response_for_single_job, true);
        $items = $api_response_for_single_job_json['items'][0];

    


        // Prepare parameters for insertion
        $params = [
            $jobId,
            !empty($items['Title']) ? $items['Title'] : (!empty($job['Title']) ? $job['Title'] : null),
            !empty($items['PostedDate']) ? $items['PostedDate'] : (!empty($job['PostedDate']) ? $job['PostedDate'] : null),
            !empty($items['PostingEndDate']) ? $items['PostingEndDate'] : (!empty($job['PostingEndDate']) ? $job['PostingEndDate'] : null),
            !empty($items['Language']) ? $items['Language'] : (!empty($job['Language']) ? $job['Language'] : null),
            !empty($items['PrimaryLocationCountry']) ? $items['PrimaryLocationCountry'] : (!empty($job['PrimaryLocationCountry']) ? $job['PrimaryLocationCountry'] : null),
            !empty($items['GeographyId']) ? $items['GeographyId'] : (!empty($job['GeographyId']) ? $job['GeographyId'] : null),
            !empty($items['HotJobFlag']) ? $items['HotJobFlag'] : (!empty($job['HotJobFlag']) ? $job['HotJobFlag'] : null),
            !empty($items['WorkplaceTypeCode']) ? $items['WorkplaceTypeCode'] : (!empty($job['WorkplaceTypeCode']) ? $job['WorkplaceTypeCode'] : null),
            !empty($items['JobFamily']) ? $items['JobFamily'] : (!empty($job['JobFamily']) ? $job['JobFamily'] : null),
            !empty($items['JobFunction']) ? $items['JobFunction'] : (!empty($job['JobFunction']) ? $job['JobFunction'] : null),
            !empty($items['WorkerType']) ? $items['WorkerType'] : (!empty($job['WorkerType']) ? $job['WorkerType'] : null),
            !empty($items['ContractType']) ? $items['ContractType'] : (!empty($job['ContractType']) ? $job['ContractType'] : null),
            !empty($items['ManagerLevel']) ? $items['ManagerLevel'] : (!empty($job['ManagerLevel']) ? $job['ManagerLevel'] : null),
            !empty($items['JobSchedule']) ? $items['JobSchedule'] : (!empty($job['JobSchedule']) ? $job['JobSchedule'] : null),
            !empty($items['JobShift']) ? $items['JobShift'] : (!empty($job['JobShift']) ? $job['JobShift'] : null),
            !empty($items['JobType']) ? $items['JobType'] : (!empty($job['JobType']) ? $job['JobType'] : null),
            !empty($items['StudyLevel']) ? $items['StudyLevel'] : (!empty($job['StudyLevel']) ? $job['StudyLevel'] : null),
            !empty($items['DomesticTravelRequired']) ? $items['DomesticTravelRequired'] : (!empty($job['DomesticTravelRequired']) ? $job['DomesticTravelRequired'] : null),
            !empty($items['InternationalTravelRequired']) ? $items['InternationalTravelRequired'] : (!empty($job['InternationalTravelRequired']) ? $job['InternationalTravelRequired'] : null),
            !empty($items['WorkDurationYears']) ? $items['WorkDurationYears'] : (!empty($job['WorkDurationYears']) ? $job['WorkDurationYears'] : null),
            !empty($items['WorkDurationMonths']) ? $items['WorkDurationMonths'] : (!empty($job['WorkDurationMonths']) ? $job['WorkDurationMonths'] : null),
            // !empty($items['WorkHours']) ? $items['WorkHours'] : (!empty($job['WorkHours']) ? $job['WorkHours'] : null),
            // !empty($items['WorkDays']) ? $items['WorkDays'] : (!empty($job['WorkDays']) ? $job['WorkDays'] : null),
            // !empty($items['Department']) ? $items['Department'] : (!empty($job['Department']) ? $job['Department'] : null),
            // !empty($items['Organization']) ? $items['Organization'] : (!empty($job['Organization']) ? $job['Organization'] : null),
            // !empty($items['MediaThumbUrl']) ? $items['MediaThumbUrl'] : (!empty($job['MediaThumbUrl']) ? $job['MediaThumbUrl'] : null),
            !empty($items['ShortDescriptionStr']) ? $items['ShortDescriptionStr'] : (!empty($job['ShortDescriptionStr']) ? $job['ShortDescriptionStr'] : null),
            !empty($items['PrimaryLocation']) ? $items['PrimaryLocation'] : (!empty($job['PrimaryLocation']) ? $job['PrimaryLocation'] : null),
            !empty($items['Distance']) ? $items['Distance'] : (!empty($job['Distance']) ? $job['Distance'] : null),
            !empty($items['TrendingFlag']) ? $items['TrendingFlag'] : (!empty($job['TrendingFlag']) ? $job['TrendingFlag'] : null),
            !empty($items['BeFirstToApplyFlag']) ? $items['BeFirstToApplyFlag'] : (!empty($job['BeFirstToApplyFlag']) ? $job['BeFirstToApplyFlag'] : null),
            !empty($items['Relevancy']) ? $items['Relevancy'] : (!empty($job['Relevancy']) ? $job['Relevancy'] : null),
            !empty($items['WorkplaceType']) ? $items['WorkplaceType'] : (!empty($job['WorkplaceType']) ? $job['WorkplaceType'] : null),
            !empty($items['ExternalQualificationsStr']) ? $items['ExternalQualificationsStr'] : (!empty($job['ExternalQualificationsStr']) ? $job['ExternalQualificationsStr'] : null),
            !empty($items['ExternalResponsibilitiesStr']) ? $items['ExternalResponsibilitiesStr'] : (!empty($job['ExternalResponsibilitiesStr']) ? $job['ExternalResponsibilitiesStr'] : null),
            !empty($items['SecondaryLocations']) && !empty($items['SecondaryLocations']) ? json_encode($items['SecondaryLocations']) : (!empty($job['SecondaryLocations']) && !empty($job['SecondaryLocations']) ? json_encode($job['SecondaryLocations']) : null),
            !empty($items['Category']) ? $items['Category'] : (!empty($job['Category']) ? $job['Category'] : null),
            !empty($items['RequisitionType']) ? $items['RequisitionType'] : (!empty($job['RequisitionType']) ? $job['RequisitionType'] : null),
            !empty($items['JobGrade']) ? $items['JobGrade'] : (!empty($job['JobGrade']) ? $job['JobGrade'] : null),
            !empty($items['RequisitionId']) ? $items['RequisitionId'] : (!empty($job['RequisitionId']) ? $job['RequisitionId'] : null),
            !empty($items['ExternalPostedStartDate']) ? $items['ExternalPostedStartDate'] : (!empty($job['ExternalPostedStartDate']) ? $job['ExternalPostedStartDate'] : null),
            !empty($items['JobLevel']) ? $items['JobLevel'] : (!empty($job['JobLevel']) ? $job['JobLevel'] : null),
            !empty($items['ExternalContactName']) ? $items['ExternalContactName'] : (!empty($job['ExternalContactName']) ? $job['ExternalContactName'] : null),
            !empty($items['ExternalContactEmail']) ? $items['ExternalContactEmail'] : (!empty($job['ExternalContactEmail']) ? $job['ExternalContactEmail'] : null),
            !empty($items['ExternalPostedEndDate']) ? $items['ExternalPostedEndDate'] : (!empty($job['ExternalPostedEndDate']) ? $job['ExternalPostedEndDate'] : null),
            !empty($items['JobFamilyId']) ? $items['JobFamilyId'] : (!empty($job['JobFamilyId']) ? $job['JobFamilyId'] : null),
            !empty($items['GeographyNodeId']) ? $items['GeographyNodeId'] : (!empty($job['GeographyNodeId']) ? $job['GeographyNodeId'] : null),
            !empty($items['ExternalDescriptionStr']) ? $items['ExternalDescriptionStr'] : (!empty($job['ExternalDescriptionStr']) ? $job['ExternalDescriptionStr'] : null),
            !empty($items['CorporateDescriptionStr']) ? $items['CorporateDescriptionStr'] : (!empty($job['CorporateDescriptionStr']) ? $job['CorporateDescriptionStr'] : null),
            !empty($items['OrganizationDescriptionStr']) ? $items['OrganizationDescriptionStr'] : (!empty($job['OrganizationDescriptionStr']) ? $job['OrganizationDescriptionStr'] : null),
            !empty($items['ContentLocale']) ? $items['ContentLocale'] : (!empty($job['ContentLocale']) ? $job['ContentLocale'] : null),
            !empty($items['ObjectVerNumberProfile']) ? $items['ObjectVerNumberProfile'] : (!empty($job['ObjectVerNumberProfile']) ? $job['ObjectVerNumberProfile'] : null),
            !empty($items['ApplyWhenNotPostedFlag']) ? $items['ApplyWhenNotPostedFlag'] : (!empty($job['ApplyWhenNotPostedFlag']) ? $job['ApplyWhenNotPostedFlag'] : null),
            !empty($items['InternalQualificationsStr']) ? $items['InternalQualificationsStr'] : (!empty($job['InternalQualificationsStr']) ? $job['InternalQualificationsStr'] : null),
            !empty($items['InternalResponsibilitiesStr']) ? $items['InternalResponsibilitiesStr'] : (!empty($job['InternalResponsibilitiesStr']) ? $job['InternalResponsibilitiesStr'] : null),
            !empty($items['requisitionFlexFields']) && !empty($items['requisitionFlexFields']) ? json_encode($items['requisitionFlexFields']) : (!empty($job['requisitionFlexFields']) && !empty($job['requisitionFlexFields']) ? json_encode($job['requisitionFlexFields']) : null),
            !empty($items['skills']) && !empty($items['skills']) ? json_encode($items['skills']) : (!empty($job['skills']) && !empty($job['skills']) ? json_encode($job['skills']) : null),
        ];
        

        $types = str_repeat("s", count($params));
        $stmt->bind_param($types, ...$params);
        
        if (!$stmt->execute()) {
            die(json_encode(["error" => "Execute failed: " . $stmt->error]));
        }
    }
}

// Cleanup
$stmt->close();
$check_stmt->close();
$conn->close();
$end_time = microtime(true);
$execution_time = $end_time - $start_time;
// Return success message
echo json_encode([
    "status"=>"success",
    "message" => "Jobs fetched and saved in database successfully from oracle",
    "execution_time" => "".round($execution_time)." seconds"
]);
?>