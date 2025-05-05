<?php
session_start();
include '../conn/conn.php'; // Include the database connection

// Check if the user is logged in and is a superadmin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'superadmin') {
    header("Location: ../login.html"); // Redirect to login if not a superadmin
    exit();
}

// Handle form submission for adding a new business
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $businessName = $conn->real_escape_string($_POST['businessName']);
    $address = $conn->real_escape_string($_POST['address']);
    $businessEmail = $conn->real_escape_string($_POST['businessEmail']);
    $businessLicense = $conn->real_escape_string($_POST['businessLicense']);
    $birNumber = $conn->real_escape_string($_POST['birNumber']);
    $businessContactNum = $conn->real_escape_string($_POST['businessContactNum']);

    // Insert the new business into the BusinessInfo table
    $sql_add_business = "INSERT INTO BusinessInfo (BusinessName, Address, BusinessEmail, BusinessLicense, BIRnumber, BusinessContactNum, status) 
                         VALUES ('$businessName', '$address', '$businessEmail', '$businessLicense', '$birNumber', '$businessContactNum', 'active')";

    if ($conn->query($sql_add_business) === TRUE) {
        echo "<script>alert('Business added successfully!'); window.location.href = 'manage_business.php';</script>";
    } else {
        echo "<script>alert('Error adding business: " . $conn->error . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Business</title>
    <style>

        body {
            background: #f1f1f1;
        }
        .container {
            max-width: 600px;
            margin: 100px auto;
            padding: 50px;
            background: #023047;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .container label {
            color: #f1f1f1;
        }
        h2 {
            color: #ff8c00;
            text-align: center;
        }
        input[type="text"], input[type="email"], textarea {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        .button-group {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .button-group button {
            padding: 10px 20px;
            background: #ff8c00;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 48%;
        }
        .button-group button:hover {
            background-color: #ff8c00;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Add New Business</h2>
    <form method="POST" action="add_business.php">
        <label for="businessName">Business Name:</label>
        <input type="text" id="businessName" name="businessName" required>

        <label for="address">Address:</label>
        <textarea id="address" name="address" rows="4" required></textarea>

        <label for="businessEmail">Business Email:</label>
        <input type="email" id="businessEmail" name="businessEmail" required>

        <label for="businessLicense">Business License:</label>
        <input type="text" id="businessLicense" name="businessLicense" required>

        <label for="birNumber">BIR Number:</label>
        <input type="text" id="birNumber" name="birNumber" required>

        <label for="businessContactNum">Business Contact Number:</label>
        <input type="text" id="businessContactNum" name="businessContactNum" required>

        <div class="button-group">
            <button type="button" onclick="window.location.href='manage_business.php';">Back</button>
            <button type="submit">Add Business</button>
        </div>
    </form>
</div>

</body>
</html>
