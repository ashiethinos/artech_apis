    <?php

    include './config.php';
    // Database credentials
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = $database_name;

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
    }


    // Define SQL query to select all data from the table
     $sql = "SELECT * FROM $table_name_jobdiva";
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



    // Close statement and database connection
    $conn->close();

    // Return success message
    echo json_encode([
        "status"=>"success",
        "from"=>"jobdiva",
        "message" => "Jobs retrieved successfully",
        "jobs" => $jobs
    ]);
    ?>
