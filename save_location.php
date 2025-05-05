<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Save Location</title>
    <style>
        body {
            background-color: #000;
            color: #ff7200;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            width: 400px;
            padding: 20px;
            background-color: #1c1c1c;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(255, 114, 0, 0.6);
            text-align: center;
        }
        h2 {
            color: #ff7200;
            margin-bottom: 10px;
        }
        label {
            color: #fff;
            font-size: 16px;
            display: block;
            margin-bottom: 10px;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: none;
            border-radius: 5px;
            background-color: #333;
            color: #ff7200;
            font-size: 16px;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #ff7200;
            color: #000;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background-color: #ff8c00;
        }
        .message {
            margin-top: 15px;
            padding: 10px;
            border-radius: 5px;
            color: #fff;
            font-size: 14px;
        }
        .success {
            background-color: #2e7d32;
        }
        .error {
            background-color: #d32f2f;
        }
    </style>
    <script>
        function saveLocation() {
            const locationInput = document.getElementById('locationInput').value.trim();
            const messageDiv = document.getElementById('message');
            
            // Clear previous messages
            messageDiv.textContent = '';
            messageDiv.className = 'message';

            if (locationInput) {
                fetch('save_location.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'customer_location=' + encodeURIComponent(locationInput)
                })
                .then(response => response.json())
                .then(data => {
                    messageDiv.textContent = data.message;
                    if (data.status === 'success') {
                        messageDiv.classList.add('success');
                    } else {
                        messageDiv.classList.add('error');
                    }
                })
                .catch(error => {
                    messageDiv.textContent = 'An error occurred. Please try again.';
                    messageDiv.classList.add('error');
                });
            } else {
                messageDiv.textContent = 'Please enter a location.';
                messageDiv.classList.add('error');
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <h2>Enter Your Location</h2>
        <label for="locationInput">Current Location:</label>
        <input type="text" id="locationInput" placeholder="e.g., First Floor, Near Entrance">
        <button onclick="saveLocation()">Save Location</button>
        <div id="message" class="message"></div>
    </div>
</body>
</html>

<?php
// Server-side response handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['customer_location'])) {
    $_SESSION['customer_location'] = $_POST['customer_location'];
    echo json_encode(['status' => 'success', 'message' => 'Location saved successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to save location']);
}
?>
