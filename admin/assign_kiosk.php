<?php
session_start();
include '../conn/conn.php'; // Ensure this is the correct path to your connection script

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kioskNum = $_POST['kioskNum'];
    $location = $_POST['location'];

    // Prepare the SQL statement to prevent SQL injection
    $sql = "UPDATE kioskdevice SET KioskLoc = ?, KioskStatus = 'assigned' WHERE KioskNum = ? AND KioskStatus != 'assigned'";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('si', $location, $kioskNum);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo "<script>alert('Kiosk assigned successfully!'); window.location.href = 'manage_kiosk.php';</script>";
            } else {
                echo "<script>alert('Kiosk is already assigned or invalid.');</script>";
            }
        } else {
            echo "<script>alert('Error assigning kiosk: " . htmlspecialchars($stmt->error) . "');</script>";
        }
    } else {
        echo "<script>alert('Error preparing statement: " . htmlspecialchars($conn->error) . "');</script>";
    }
}

// Fetch all kiosks for dropdown, including only kiosks that are not assigned
$sql = "SELECT KioskNum, KioskLoc FROM kioskdevice WHERE KioskStatus != 'assigned' ORDER BY KioskNum";
$result = $conn->query($sql);
$kiosks = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $kiosks[] = $row;
    }
} else {
    echo "<script>alert('Error fetching kiosks: " . htmlspecialchars($conn->error) . "');</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Kiosk</title>
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
            color: #ffffff;
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
            gap: 10px;
            margin-top: 20px;
        }
        .button-group button {
            flex: 1;
            padding: 10px;
            background-color: #ff7200;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }
        .button-group button:hover {
            background-color: #ff8c00;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Assign Kiosk</h2>
    <form method="post" action="assign_kiosk.php">
        <label for="kioskNum">Kiosk Number:</label>
        <select name="kioskNum" id="kioskNum" onchange="updateLocation()">
            <?php if (empty($kiosks)) { ?>
                <option disabled>No kiosks available</option>
            <?php } else { ?>
                <?php foreach ($kiosks as $kiosk) { ?>
                    <option value="<?php echo $kiosk['KioskNum']; ?>" data-loc="<?php echo htmlspecialchars($kiosk['KioskLoc']); ?>">
                        <?php echo $kiosk['KioskNum']; ?>
                    </option>
                <?php } ?>
            <?php } ?>
        </select>
        <label for="location">Location:</label>
        <input type="text" id="location" name="location" required>
        <div class="button-group">
            <button type="button" onclick="window.location.href='manage_kiosk.php';">Back</button>
            <button type="submit">Assign Kiosk</button>
        </div>
    </form>
</div>

<script>
function updateLocation() {
    var select = document.getElementById('kioskNum');
    var locationInput = document.getElementById('location');
    var selectedOption = select.options[select.selectedIndex];
    locationInput.value = selectedOption.getAttribute('data-loc'); // Update the location input with the data-loc attribute of the selected option
}
</script>

</body>
</html>
