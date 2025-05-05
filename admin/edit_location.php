<?php
session_start();
include '../conn/conn.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check admin login
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.html");
    exit();
}

$businessID = isset($_SESSION['business_id']) ? (int)$_SESSION['business_id'] : null;
if (!$businessID) {
    echo "<script>alert('No business assigned.'); window.location.href = '../login.html';</script>";
    exit();
}

// Get location ID
$locID = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;
if (!$locID) {
    echo "<script>alert('Invalid Location ID'); window.location.href = 'manage_location.php';</script>";
    exit();
}

ob_start();

// Fetch location details
$sql = "SELECT LocName, FloorLevel, status FROM location WHERE LocID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $locID);
$stmt->execute();
$result = $stmt->get_result();
$location = $result->num_rows > 0 ? $result->fetch_assoc() : null;
$stmt->close();
if (!$location) {
    echo "<script>alert('Location not found'); window.location.href = 'manage_location.php';</script>";
    exit();
}

// Fetch current images and album ID
$sql_images = "SELECT li.ImageID, li.Filename, li.MapCode, la.AlbumID, la.Description
               FROM locationimage li
               INNER JOIN location_albums la ON li.AlbumID = la.AlbumID
               WHERE la.LocID = ?";
$stmt_images = $conn->prepare($sql_images);
$stmt_images->bind_param("i", $locID);
$stmt_images->execute();
$result_images = $stmt_images->get_result();
$images = [];
$albumID = null;
while ($row = $result_images->fetch_assoc()) {
    $images[] = $row;
    $albumID = $row['AlbumID']; // Assuming one album per location
}
$stmt_images->close();

// Fetch all available images
$sql_all_images = "SELECT li.ImageID, li.Filename, li.MapCode, la.Description, la.LocID 
                   FROM locationimage li
                   LEFT JOIN location_albums la ON li.AlbumID = la.AlbumID";
$stmt_all_images = $conn->prepare($sql_all_images);
$stmt_all_images->execute();
$result_all_images = $stmt_all_images->get_result();
$available_images = [];
$used_images = []; // Track used ImageIDs
while ($row = $result_all_images->fetch_assoc()) {
    $available_images[$row['ImageID']] = $row;
    if ($row['LocID'] && $row['LocID'] != $locID) {
        $used_images[$row['ImageID']] = true; // Mark as used if assigned to another LocID
    }
}
$stmt_all_images->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST detected at " . date('Y-m-d H:i:s'));
    error_log("Raw POST data: " . print_r($_POST, true));
    echo "<pre>POST Received at " . date('Y-m-d H:i:s') . ":\n"; print_r($_POST); echo "</pre>";

    $locName = $conn->real_escape_string($_POST['locName'] ?? '');
    $floorLevel = $conn->real_escape_string($_POST['floorLevel'] ?? '');
    $status = $conn->real_escape_string($_POST['status'] ?? '');
    $image_ids = $_POST['image_id_select'] ?? [];
    $descriptions = $_POST['description'] ?? [];

    $conn->begin_transaction();
    try {
        // Update location
        $sql_update = "UPDATE location SET LocName = ?, FloorLevel = ?, status = ? WHERE LocID = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("sssi", $locName, $floorLevel, $status, $locID);
        if (!$stmt_update->execute()) {
            throw new Exception("Location update failed: " . $stmt_update->error);
        }
        error_log("Location update executed for LocID=$locID, LocName=$locName");
        $stmt_update->close();

        // Update album description (assuming one album per location)
        if ($albumID && isset($descriptions[0])) {
            $newDescription = $conn->real_escape_string($descriptions[0]);
            $sql_update_album = "UPDATE location_albums SET Description = ? WHERE AlbumID = ? AND LocID = ?";
            $stmt_update_album = $conn->prepare($sql_update_album);
            $stmt_update_album->bind_param("sii", $newDescription, $albumID, $locID);
            if (!$stmt_update_album->execute()) {
                throw new Exception("Album update failed: " . $stmt_update_album->error);
            }
            error_log("Album updated: AlbumID=$albumID, Description=$newDescription");
            $stmt_update_album->close();
        }

        // Update images (Filename and MapCode)
        foreach ($images as $index => $image) {
            $imageID = $image['ImageID'];
            $newImageID = isset($image_ids[$index]) && $image_ids[$index] !== '' ? (int)$image_ids[$index] : $imageID;

            error_log("Processing ImageID=$imageID, New ImageID=$newImageID");

            if ($newImageID !== $imageID) {
                if (!isset($available_images[$newImageID])) {
                    throw new Exception("Invalid New ImageID: $newImageID");
                }
                if (isset($used_images[$newImageID])) {
                    throw new Exception("Cannot use ImageID=$newImageID: It is already assigned to another location.");
                }
                $newFilename = $available_images[$newImageID]['Filename'];
                $newMapCode = $available_images[$newImageID]['MapCode'];
                $filePath = "../uploads/floorplans/" . $newFilename;

                error_log("Checking file: $filePath");
                if (!file_exists($filePath)) {
                    throw new Exception("File not found: $filePath");
                }

                $sql_update_image = "UPDATE locationimage SET Filename = ?, MapCode = ? WHERE ImageID = ?";
                $stmt_update_image = $conn->prepare($sql_update_image);
                $stmt_update_image->bind_param("ssi", $newFilename, $newMapCode, $imageID);
                if (!$stmt_update_image->execute()) {
                    throw new Exception("Image update failed: " . $stmt_update_image->error);
                }
                error_log("Image updated: ImageID=$imageID, Filename=$newFilename, MapCode=$newMapCode");
                $stmt_update_image->close();
            } else {
                error_log("No change for ImageID=$imageID");
            }
        }

        $conn->commit();
        error_log("Transaction committed for LocID=$locID");
        ob_end_clean();
        echo "<script>alert('Location updated!'); window.location.href = 'manage_location.php';</script>";
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error: " . $e->getMessage());
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
    }
} else {
    error_log("No POST detected at " . date('Y-m-d H:i:s'));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Location</title>
    <style>
        body { font-family: Arial, sans-serif; background: #fff; }
        .container { width: 600px; margin: 50px auto; background: #023047; padding: 30px; border-radius: 10px; }
        label { color: #fff; }
        h2 { text-align: center; color: #ff7200; }
        input[type="text"], select { width: 100%; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .button-group { display: flex; justify-content: space-between; }
        input[type="submit"], button { width: 48%; padding: 10px; background-color: #ff7200; color: #fff; border: none; border-radius: 5px; cursor: pointer; }
        input[type="submit"]:hover, button:hover { background-color: #ff8c00; }
        .image-preview { margin-top: 20px; }
        .image-preview p { color: #fff; }
        .album-gallery { display: flex; flex-wrap: wrap; gap: 10px; }
        .album-gallery img { max-width: 150px; max-height: 150px; }
        .album-gallery .image-item { position: relative; }
        .album-gallery .image-item p { font-size: 12px; color: #fff; }
        .image-select { width: 100%; margin-top: 5px; }
        .updated-image { display: none; margin-top: 10px; }
        pre { background: #f0f0f0; padding: 10px; }
        .used-option { color: red; }
        .description-input { width: 100%; padding: 10px; margin: 5px 0; border-radius: 5px; }
    </style>
    <script>
        function updateImage(selectElement, imageId) {
            console.log('updateImage called for ImageID:', imageId, 'Selected ImageID:', selectElement.value);
            const selectedImageID = selectElement.value;
            const imageItem = selectElement.closest('.image-item');
            const currentImage = imageItem.querySelector('.current-image');
            const updatedImage = imageItem.querySelector('.updated-image');
            const availableImages = <?php echo json_encode($available_images); ?>;

            if (selectedImageID && availableImages[selectedImageID]) {
                updatedImage.src = "../uploads/floorplans/" + availableImages[selectedImageID].Filename;
                updatedImage.style.display = 'block';
                currentImage.style.opacity = '0.5';
                console.log('Image updated to:', updatedImage.src);
            } else {
                updatedImage.style.display = 'none';
                currentImage.style.opacity = '1';
                console.log('No image update');
            }
        }
    </script>
</head>
<body>
<div class="container">
    <h2>Edit Location</h2>
    <form method="POST" action="edit_location.php?id=<?php echo $locID; ?>" onsubmit="console.log('Form submitted');">
        <input type="hidden" name="submit" value="1">
        <label for="locName">Location Name</label>
        <input type="text" id="locName" name="locName" value="<?php echo htmlspecialchars($location['LocName'] ?? ''); ?>" required>

        <label for="floorLevel">Floor Level</label>
        <input type="text" id="floorLevel" name="floorLevel" value="<?php echo htmlspecialchars($location['FloorLevel'] ?? ''); ?>" required>

        <label for="status">Status</label>
        <select id="status" name="status" required>
            <option value="active" <?php echo ($location['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
            <option value="inactive" <?php echo ($location['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
        </select>

        <div class="image-preview">
            <p>Current Images:</p>
            <?php if (!empty($images)) { ?>
                <div class="album-gallery">
                    <?php foreach ($images as $index => $image) { ?>
                        <div class="image-item">
                            <img src="../uploads/floorplans/<?php echo htmlspecialchars($image['Filename'] ?? ''); ?>" alt="Current Image" class="current-image">
                            <p>MapCode: <?php echo htmlspecialchars($image['MapCode'] ?? ''); ?></p>
                            <select name="image_id_select[<?php echo $index; ?>]" class="image-select" onchange="updateImage(this, '<?php echo $image['ImageID']; ?>')">
                                <option value="">Keep (<?php echo htmlspecialchars($image['MapCode'] ?? ''); ?>)</option>
                                <?php foreach ($available_images as $avail_image) { ?>
                                    <option value="<?php echo $avail_image['ImageID']; ?>" 
                                            <?php echo $avail_image['ImageID'] === $image['ImageID'] ? 'selected' : ''; ?> 
                                            class="<?php echo isset($used_images[$avail_image['ImageID']]) ? 'used-option' : ''; ?>"
                                            <?php echo isset($used_images[$avail_image['ImageID']]) ? 'disabled' : ''; ?>>
                                        <?php echo htmlspecialchars($avail_image['MapCode'] ?? '') . (isset($used_images[$avail_image['ImageID']]) ? ' - Used' : ''); ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <label for="description_<?php echo $index; ?>">Description</label>
                            <input type="text" id="description_<?php echo $index; ?>" name="description[<?php echo $index; ?>]" 
                                   class="description-input" value="<?php echo htmlspecialchars($image['Description'] ?? ''); ?>" required>
                            <img src="" alt="Updated Image" class="updated-image">
                        </div>
                    <?php } ?>
                </div>
            <?php } else { ?>
                <p>No images.</p>
            <?php } ?>
        </div>

        <div class="button-group">
            <button type="button" onclick="window.location.href='manage_location.php';">Back</button>
            <input type="submit" value="Update Location" onclick="console.log('Submit clicked');">
        </div>
    </form>
</div>

<?php
$conn->close();
ob_end_flush();
?>
</body>
</html>