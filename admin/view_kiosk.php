<?php
session_start();
include '../conn/conn.php'; // Include the database connection

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.html");
    exit();
}

// Check if a kiosk ID is provided
if (!isset($_GET['id'])) {
    echo "<script>alert('Invalid kiosk ID'); window.location.href = 'manage_kiosk.php';</script>";
    exit();
}

$kioskID = $_GET['id'];

// Fetch the kiosk details
$sql = "SELECT * FROM kioskdevice WHERE KioskID = '$kioskID'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    echo "<script>alert('Kiosk not found'); window.location.href = 'manage_kiosk.php';</script>";
    exit();
}

$kiosk = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Kiosk Device</title>
    <style>

        body {
            background: #ffff;
        }
        .container { width: 800px; margin: 50px auto; background: #023047; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        h2 { text-align: center; color: #ff7200; }
        .details { margin-top: 20px; color: #ffff; }
        .details p { font-size: 18px; margin: 10px 0; }
        .button-group { text-align: center; margin-top: 20px; }
        .button-group button { padding: 10px 20px; background-color: #ff7200; color: #fff; border: none; border-radius: 5px; cursor: pointer; }
        .button-group button:hover { background-color: #ff8c00; }
    </style>
</head>
<body>

<div class="container">
    <h2>Kiosk Device Details</h2>

    <div class="details">
        <p><strong>Kiosk ID:</strong> <?php echo $kiosk['KioskID']; ?></p>
        <p><strong>Location:</strong> <?php echo htmlspecialchars($kiosk['KioskLoc']); ?></p>
        <p><strong>Number:</strong> <?php echo htmlspecialchars($kiosk['KioskNum']); ?></p>
        <p><strong>Status:</strong> <?php echo ucfirst($kiosk['KioskStatus']); ?></p>
    </div>

    <!-- Back button -->
    <div class="button-group">
        <button onclick="window.location.href='manage_kiosk.php';">Back</button>
    </div>
</div>

</body>
</html>
