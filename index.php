<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HTTP Request Example</title>
    <script>
        function sendGetRequest() {
            fetch(`fetch_and_save.php`)
                .then(response => response.json())
                .catch(error => {
                    document.getElementById('result').textContent = `Error: ${error}`;
                });
        }

      
    </script>
</head>
<body>
    <h1>HTTP Request Example</h1>
  
    <button onclick="sendGetRequest()">Send GET Request</button>

    <p id="result"></p>
</body>
</html>
