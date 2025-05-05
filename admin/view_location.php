<?php
session_start();
include '../conn/conn.php'; // Include the database connection

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.html");
    exit();
}

// Check if a location ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('Invalid location ID'); window.location.href = 'manage_location.php';</script>";
    exit();
}

$locID = (int)$_GET['id']; // Cast to integer for safety

// Fetch the location details with images from albums
$sql = "
    SELECT 
        l.LocID, l.LocName, l.FloorLevel, l.StoreID, l.KioskID, l.status,
        s.StoreBrandName,
        k.KioskNum,
        GROUP_CONCAT(DISTINCT li.Filename SEPARATOR '|') AS Filenames
    FROM location l
    LEFT JOIN store s ON l.StoreID = s.StoreID
    LEFT JOIN kioskdevice k ON l.KioskID = k.KioskID
    LEFT JOIN location_albums la ON l.LocID = la.LocID
    LEFT JOIN locationimage li ON la.AlbumID = li.AlbumID
    WHERE l.LocID = ?
    GROUP BY l.LocID, l.LocName, l.FloorLevel, l.StoreID, l.KioskID, l.status, s.StoreBrandName, k.KioskNum";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $locID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<script>alert('Location not found'); window.location.href = 'manage_location.php';</script>";
    exit();
}

$location = $result->fetch_assoc();
$filenames = $location['Filenames'] ? explode('|', $location['Filenames']) : [];
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Location</title>
    <style>
        body {
            background: #fff;
            font-family: Arial, sans-serif;
        }
        .container {
            width: 800px;
            margin: 50px auto;
            background: #023047;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            color: #ff7200;
            font-size: 28px;
        }
        .location-details p {
            font-size: 18px;
            margin: 8px 0;
            color: #fff;
        }
        .location-image {
            margin-top: 15px;
        }
        .location-image img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 10px; /* Space between images */
        }
        .button-group {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        .button-group button {
            padding: 12px 25px;
            background-color: #ff7200;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .button-group button:hover {
            background-color: #ff8c00;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>View Location</h2>
    <div class="location-details">
        <p><strong>Location Name:</strong> <?php echo htmlspecialchars($location['LocName']); ?></p>
        <p><strong>Floor Level:</strong> <?php echo htmlspecialchars($location['FloorLevel']); ?></p>
        <p><strong>Assigned Store:</strong> <?php echo htmlspecialchars($location['StoreBrandName'] ?? 'None'); ?></p>
        <p><strong>Assigned Kiosk:</strong> <?php echo htmlspecialchars($location['KioskNum'] ?? 'None'); ?></p>
        <p><strong>Status:</strong> <?php echo ucfirst($location['status']); ?></p>
    </div>

    <!-- Display Images if available -->
    <div class="location-image">
        <?php if (!empty($filenames)) { ?>
            <?php foreach ($filenames as $filename) { ?>
                <img src="../uploads/floorplans/<?php echo htmlspecialchars($filename); ?>" alt="Map/Floorplan Image">
            <?php } ?>
        <?php } else { ?>
            <p><em>No images available</em></p>
        <?php } ?>
    </div>

    <div class="button-group">
        <button onclick="window.location.href='manage_location.php';">Back</button>
    </div>
</div>

</body>
</html>