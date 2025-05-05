<?php
session_start();
include '../conn/conn.php'; // Include the database connection

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.html"); // Redirect to login if not an admin
    exit();
}

// Get the kiosk ID from the URL
if (isset($_GET['id'])) {
    $kioskID = $_GET['id'];
} else {
    echo "<script>alert('Invalid Kiosk ID'); window.location.href = 'manage_kiosk.php';</script>";
    exit();
}

// Fetch kiosk details
$sql_kiosk = "SELECT KioskID, KioskStatus, KioskLoc, KioskNum, KioskCode, BusinessID, StoreID, status 
              FROM kioskdevice 
              WHERE KioskID = ?";
$stmt = $conn->prepare($sql_kiosk);
$stmt->bind_param("i", $kioskID);
$stmt->execute();
$result_kiosk = $stmt->get_result();
if ($result_kiosk->num_rows > 0) {
    $kiosk = $result_kiosk->fetch_assoc();
} else {
    echo "<script>alert('Kiosk not found'); window.location.href = 'manage_kiosk.php';</script>";
    exit();
}

// Handle form submission for updating the kiosk
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kioskStatus = $conn->real_escape_string($_POST['kioskStatus']);
    $kioskLoc = $conn->real_escape_string($_POST['kioskLoc']);
    $kioskNum = $conn->real_escape_string($_POST['kioskNum']);
    $kioskCode = $conn->real_escape_string($_POST['kioskCode']);

    // Update the kiosk details
    $sql_update = "UPDATE kioskdevice SET KioskStatus='$kioskStatus', KioskLoc='$kioskLoc', KioskNum='$kioskNum', KioskCode='$kioskCode' WHERE KioskID='$kioskID'";
    if ($conn->query($sql_update) === TRUE) {
        echo "<script>alert('Kiosk updated successfully!'); window.location.href = 'manage_kiosk.php';</script>";
    } else {
        echo "<script>alert('Error updating kiosk: " . $conn->error . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kiosk Device</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #ffff;
        }
        .container {
            width: 600px;
            margin: 50px auto;
            background: #023047;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .container label {
            color: #ffff;
        } 
        h2 {
            text-align: center;
            color: #ff7200;
        }
        input[type="text"], select {
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
            width: 48%;
            padding: 10px;
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
    <h2>Edit Kiosk Device</h2>

    <!-- Edit Kiosk Form -->
    <form method="POST" action="edit_kiosk.php?id=<?php echo $kioskID; ?>">
        <label for="kioskLoc">Kiosk Location</label>
        <input type="text" id="kioskLoc" name="kioskLoc" value="<?php echo htmlspecialchars($kiosk['KioskLoc']); ?>" required>

        <label for="kioskNum">Kiosk Number</label>
        <input type="text" id="kioskNum" name="kioskNum" value="<?php echo htmlspecialchars($kiosk['KioskNum']); ?>" required>

        <label for="kioskCode">Kiosk Code</label>
        <input type="text" id="kioskCode" name="kioskCode" value="<?php echo htmlspecialchars($kiosk['KioskCode'] ?? ''); ?>" required>

        <label for="kioskStatus">Kiosk Status</label>
        <select id="kioskStatus" name="kioskStatus" required>
            <option value="active" <?php echo ($kiosk['KioskStatus'] == 'active') ? 'selected' : ''; ?>>Active</option>
            <option value="inactive" <?php echo ($kiosk['KioskStatus'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
        </select>

        <!-- Back and Update buttons in one row -->
        <div class="button-group">
            <button type="button" onclick="window.location.href='manage_kiosk.php';">Back</button>
            <button type="submit">Update Kiosk</button>
        </div>
    </form>
</div>

</body>
</html>
