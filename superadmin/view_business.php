<?php
session_start();
include '../conn/conn.php'; // Include the database connection

// Check if a business ID is provided
if (!isset($_GET['id'])) {
    echo "<script>alert('Business ID is missing.'); window.location.href = 'manage_business.php';</script>";
    exit();
}

$businessID = $_GET['id'];

// Fetch business details
$sql_business = "SELECT * FROM BusinessInfo WHERE BusinessID = '$businessID'";
$result_business = $conn->query($sql_business);

if ($result_business->num_rows > 0) {
    $business = $result_business->fetch_assoc();
} else {
    echo "<script>alert('Business not found.'); window.location.href = 'manage_business.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Business</title>
    <style>

        body {
            background: #ffff;
        }
        .container {
            max-width: 600px;
            font-size: 30px;
            margin: 150px auto;
            padding: 30px;
            background: #023047;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #ffff;
            text-align: center;
        }
        .business-details p {
            font-size: 30px;
            margin: 10px 0;
        }
        label: {
            color: #ffff;
        }
        p {
            color: #ffff;
        }
        .button-group {
            text-align: center;
            margin-top: 20px;
        }
        .button-group button {
            padding: 10px 20px;
            background-color: #ff7200;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .button-group button:hover {
            background-color: #ff8c00;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Business Details</h2>
    <div class="business-details">
        <p><strong>Business Name:</strong> <?php echo htmlspecialchars($business['BusinessName']); ?></p>
        <p><strong>Address:</strong> <?php echo htmlspecialchars($business['Address']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($business['BusinessEmail']); ?></p>
        <p><strong>Business License:</strong> <?php echo htmlspecialchars($business['BusinessLicense']); ?></p>
        <p><strong>BIR Number:</strong> <?php echo htmlspecialchars($business['BIRnumber']); ?></p>
        <p><strong>Contact Number:</strong> <?php echo htmlspecialchars($business['BusinessContactNum']); ?></p>
        <p><strong>Status:</strong> <?php echo ucfirst($business['status']); ?></p>
    </div>
    <div class="button-group">
        <button onclick="window.location.href='manage_business.php'">Back</button>
    </div>
</div>

</body>
</html>
