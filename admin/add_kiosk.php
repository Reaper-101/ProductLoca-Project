<?php
session_start();
include '../conn/conn.php'; // Include the database connection

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.html");
    exit();
}

// Get the assigned BusinessID for the logged-in admin
$businessID = isset($_SESSION['business_id']) ? $_SESSION['business_id'] : null;

if (!$businessID) {
    echo "<script>alert('No business assigned to this admin. Please contact the Super Admin.'); window.location.href = '../login.html';</script>";
    exit();
}

// Handle form submission for adding a new kiosk device
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kioskLoc = $conn->real_escape_string($_POST['kioskLoc']);
    $kioskNum = $conn->real_escape_string($_POST['kioskNum']);
    $kioskCode = $conn->real_escape_string($_POST['kioskCode']);

    // Insert into the kioskdevice table with default status 'active' and BusinessID
    $sql_kiosk = "INSERT INTO kioskdevice (KioskLoc, KioskNum, kioskCode, BusinessID, KioskStatus) 
                  VALUES ('$kioskLoc', '$kioskNum','$kioskCode', '$businessID', 'active')";
    if ($conn->query($sql_kiosk) === TRUE) {
        echo "<script>alert('Kiosk device added successfully!'); window.location.href = 'manage_kiosk.php';</script>";
    } else {
        echo "<script>alert('Error adding kiosk device: " . $conn->error . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Kiosk Device</title>
    <style>

        
        body {
            background: #ffff; 
        }
        .container { width: 800px; margin: 50px auto;  background: #023047;  padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        .container label{color: #ffff;}
        h2 { text-align: center; color: #ff7200; }
        input[type="text"] { width: 100%; padding: 10px; margin: 10px 0; border-radius: 5px; border: 1px solid #ccc; }
        .button-group { display: flex; justify-content: space-between; margin-top: 20px; }
        .button-group button { width: 48%; padding: 10px; background-color: #ff7200; color: #fff; border: none; border-radius: 5px; cursor: pointer; }
        .button-group button:hover { background-color: #ff8c00; }
    </style>
</head>
<body>

<div class="container">
    <h2>Add New Kiosk Device</h2>

    <!-- Add Kiosk Device Form -->
    <form method="POST" action="add_kiosk.php">
        <label for="kioskLoc">Kiosk Location</label>
        <input type="text" id="kioskLoc" name="kioskLoc" required>

        <label for="kioskNum">Kiosk Number</label>
        <input type="text" id="kioskNum" name="kioskNum" required>

        <label for="kioskCode">Kiosk Code</label>
        <input type="text" id="kioskCode" name="kioskCode" required>

        <!-- Back and Add Kiosk Device buttons -->
        <div class="button-group">
            <button type="button" onclick="window.location.href='manage_kiosk.php';">Back</button>
            <button type="submit">Add Kiosk Device</button>
        </div>
    </form>
</div>

</body>
</html>
