<?php
session_start();
include '../conn/conn.php'; // Include the database connection

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.html");
    exit();
}

// Get the assigned BusinessID for the logged-in admin
$businessID = isset($_SESSION['business_id']) ? (int)$_SESSION['business_id'] : null;

if (!$businessID) {
    echo "<script>alert('No business assigned to this admin. Please contact the Super Admin.'); window.location.href = '../login.html';</script>";
    exit();
}

// Fetch available stores and kiosks for the current business
$sql_stores = "SELECT StoreID, StoreBrandName FROM store WHERE BusinessID = ? AND status = 'active'";
$stmt_stores = $conn->prepare($sql_stores);
$stmt_stores->bind_param("i", $businessID);
$stmt_stores->execute();
$result_stores = $stmt_stores->get_result();

$sql_kiosks = "SELECT KioskID, KioskNum FROM kioskdevice WHERE BusinessID = ? AND status = 'active'";
$stmt_kiosks = $conn->prepare($sql_kiosks);
$stmt_kiosks->bind_param("i", $businessID);
$stmt_kiosks->execute();
$result_kiosks = $stmt_kiosks->get_result();

// Fetch all floorplan images and check their usage status
$sql_floorplans = "SELECT li.ImageID, li.MapCode, la.AlbumName, la.LocID 
                  FROM locationimage li 
                  JOIN location_albums la ON li.AlbumID = la.AlbumID";
$stmt_floorplans = $conn->prepare($sql_floorplans);
$stmt_floorplans->execute();
$result_floorplans = $stmt_floorplans->get_result();
$floorplans = [];
$used_mapcodes = [];
while ($row = $result_floorplans->fetch_assoc()) {
    $floorplans[] = $row;
    if ($row['LocID'] !== null) {
        $used_mapcodes[$row['MapCode']] = true; // Mark as used if LocID is set
    }
}

// Handle form submission for adding a new location with albums
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $locName = $conn->real_escape_string($_POST['locName']);
    $floorLevel = $conn->real_escape_string($_POST['floorLevel']);
    $storeID = !empty($_POST['storeID']) ? (int)$_POST['storeID'] : NULL;
    $kioskID = !empty($_POST['kioskID']) ? (int)$_POST['kioskID'] : NULL;
    $status = $conn->real_escape_string($_POST['status']);
    $albumNames = $_POST['albumName']; // Array of album names
    $albumDescriptions = $_POST['albumDescription']; // Array of album descriptions
    $mapCodes = $_POST['mapCodes']; // Array of selected MapCodes for each album

    // Insert into the location table
    $sql_loc = "INSERT INTO location (LocName, FloorLevel, BusinessID, StoreID, KioskID, status) 
                VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_loc = $conn->prepare($sql_loc);
    $stmt_loc->bind_param("ssiiis", $locName, $floorLevel, $businessID, $storeID, $kioskID, $status);

    if ($stmt_loc->execute()) {
        $locID = $conn->insert_id;
        $success = true;
        $total_images_assigned = 0;

        // Process each album
        foreach ($albumNames as $index => $albumName) {
            if (!empty($albumName)) {
                $albumDesc = $conn->real_escape_string($albumDescriptions[$index] ?? 'Default Album Description');

                // Insert album into location_albums table
                $sql_album = "INSERT INTO location_albums (LocID, AlbumName, Description) VALUES (?, ?, ?)";
                $stmt_album = $conn->prepare($sql_album);
                $stmt_album->bind_param("iss", $locID, $albumName, $albumDesc);
                if ($stmt_album->execute()) {
                    $albumID = $conn->insert_id;

                    // Assign selected MapCodes to this album
                    if (isset($mapCodes[$index]) && !empty($mapCodes[$index])) {
                        foreach ($mapCodes[$index] as $mapCode) {
                            // Check if MapCode is already used
                            if (isset($used_mapcodes[$mapCode])) {
                                $success = false;
                                echo "<script>alert('MapCode $mapCode is already assigned to another location and cannot be used.');</script>";
                                continue;
                            }

                            // Update the image's AlbumID to link it to this new album
                            $sql_update_image = "UPDATE locationimage li
                                                JOIN location_albums la ON li.AlbumID = la.AlbumID
                                                SET li.AlbumID = ?
                                                WHERE li.MapCode = ? AND la.LocID IS NULL";
                            $stmt_update_image = $conn->prepare($sql_update_image);
                            $stmt_update_image->bind_param("is", $albumID, $mapCode);
                            if ($stmt_update_image->execute() && $stmt_update_image->affected_rows > 0) {
                                $total_images_assigned++;
                                $used_mapcodes[$mapCode] = true; // Mark as used
                            } else {
                                $success = false;
                                echo "<script>alert('Error assigning MapCode $mapCode to album $albumName: " . addslashes($stmt_update_image->error) . "');</script>";
                            }
                            $stmt_update_image->close();
                        }
                    }
                } else {
                    $success = false;
                    echo "<script>alert('Error creating album $albumName: " . addslashes($stmt_album->error) . "');</script>";
                }
                $stmt_album->close();
            }
        }

        if ($success) {
            echo "<script>alert('Location and $total_images_assigned floorplan images across " . count(array_filter($albumNames)) . " albums added successfully!'); window.location.href = 'manage_location.php';</script>";
        } else {
            echo "<script>alert('Location added, but some albums or images failed to process.');</script>";
        }
    } else {
        echo "<script>alert('Error adding location: " . addslashes($stmt_loc->error) . "');</script>";
    }
    $stmt_loc->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Location</title>
    <style>
        body { background: #fff; font-family: Arial, sans-serif; }
        .container { width: 800px; margin: 50px auto; background: #023047; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        .container label { color: #fff; }
        .container input, .container select { background: #fff; }
        h2 { text-align: center; color: #ff7200; }
        input[type="text"], select { width: 100%; padding: 10px; margin: 10px 0; border-radius: 5px; border: 1px solid #ccc; box-sizing: border-box; }
        .album-group { margin: 20px 0; padding: 15px; background: #034061; border-radius: 5px; }
        .album-group h3 { color: #ff7200; margin-top: 0; }
        .add-album-btn { display: inline-block; padding: 8px 15px; background: #ff7200; color: #fff; border: none; border-radius: 5px; cursor: pointer; margin-top: 10px; }
        .add-album-btn:hover { background: #ff8c00; }
        .button-group { display: flex; justify-content: space-between; }
        .button-group button { width: 48%; padding: 10px; background-color: #ff7200; color: #fff; border: none; border-radius: 5px; cursor: pointer; }
        .button-group button:hover { background-color: #ff8c00; }
        select[multiple] { height: 150px; }
        .used-mapcode { color: red; }
    </style>
</head>
<body>
<div class="container">
    <h2>Add New Location</h2>
    <form method="POST" enctype="multipart/form-data">
        <label for="locName">Location Name</label>
        <input type="text" id="locName" name="locName" required>

        <label for="floorLevel">Floor Level</label>
        <input type="text" id="floorLevel" name="floorLevel" required>

        <label for="storeID">Assign Store</label>
        <select id="storeID" name="storeID">
            <option value="">No Store (Optional)</option>
            <?php while ($store = $result_stores->fetch_assoc()) { ?>
                <option value="<?php echo $store['StoreID']; ?>"><?php echo htmlspecialchars($store['StoreBrandName']); ?></option>
            <?php } ?>
        </select>

        <label for="kioskID">Assign Kiosk Device</label>
        <select id="kioskID" name="kioskID">
            <option value="">No Kiosk (Optional)</option>
            <?php while ($kiosk = $result_kiosks->fetch_assoc()) { ?>
                <option value="<?php echo $kiosk['KioskID']; ?>"><?php echo htmlspecialchars($kiosk['KioskNum']); ?></option>
            <?php } ?>
        </select>

        <label for="status">Status</label>
        <select id="status" name="status" required>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>

        <!-- Album Section -->
        <div id="albums">
            <div class="album-group">
                <h3>Album 1</h3>
                <label for="albumName_0">Album Name</label>
                <input type="text" id="albumName_0" name="albumName[]" required placeholder="e.g., Ground Floor Maps">
                
                <label for="albumDescription_0">Album Description (Optional)</label>
                <input type="text" id="albumDescription_0" name="albumDescription[]" placeholder="e.g., Maps for ground floor stores">
                
                <label for="mapCodes_0">Select Floorplan Images (Map Codes)</label>
                <select id="mapCodes_0" name="mapCodes[0][]" multiple>
                    <?php foreach ($floorplans as $floorplan) { ?>
                        <option value="<?php echo htmlspecialchars($floorplan['MapCode']); ?>" 
                                <?php echo isset($used_mapcodes[$floorplan['MapCode']]) ? 'disabled class="used-mapcode"' : ''; ?>>
                            <?php echo htmlspecialchars($floorplan['MapCode']) . " (" . htmlspecialchars($floorplan['AlbumName']) . ")" . (isset($used_mapcodes[$floorplan['MapCode']]) ? " - Already Used" : ""); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
        </div>
        <button type="button" class="add-album-btn" onclick="addAlbum()">Add Another Album</button>

        <div class="button-group">
            <button type="button" onclick="window.location.href='manage_location.php';">Back</button>
            <button type="submit">Add Location</button>
        </div>
    </form>
</div>

<script>
    let albumCount = 1;

    function addAlbum() {
        const albumsDiv = document.getElementById('albums');
        const newAlbum = document.createElement('div');
        newAlbum.className = 'album-group';
        newAlbum.innerHTML = `
            <h3>Album ${albumCount + 1}</h3>
            <label for="albumName_${albumCount}">Album Name</label>
            <input type="text" id="albumName_${albumCount}" name="albumName[]" required placeholder="e.g., Second Floor Maps">
            
            <label for="albumDescription_${albumCount}">Album Description (Optional)</label>
            <input type="text" id="albumDescription_${albumCount}" name="albumDescription[]" placeholder="e.g., Maps for second floor stores">
            
            <label for="mapCodes_${albumCount}">Select Floorplan Images (Map Codes)</label>
            <select id="mapCodes_${albumCount}" name="mapCodes[${albumCount}][]" multiple>
                <?php foreach ($floorplans as $floorplan) { ?>
                    <option value="<?php echo htmlspecialchars($floorplan['MapCode']); ?>" 
                            <?php echo isset($used_mapcodes[$floorplan['MapCode']]) ? 'disabled class="used-mapcode"' : ''; ?>>
                        <?php echo htmlspecialchars($floorplan['MapCode']) . " (" . htmlspecialchars($floorplan['AlbumName']) . ")" . (isset($used_mapcodes[$floorplan['MapCode']]) ? " - Already Used" : ""); ?>
                    </option>
                <?php } ?>
            </select>
        `;
        albumsDiv.appendChild(newAlbum);
        albumCount++;
    }
</script>

</body>
</html>
<?php
$stmt_stores->close();
$stmt_kiosks->close();
$stmt_floorplans->close();
?>