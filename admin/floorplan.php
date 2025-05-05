<?php
session_start();
include '../conn/conn.php'; // Database connection

// Check if user is an admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin' || !isset($_SESSION['business_id'])) {
    header("Location: ../login.html");
    exit();
}

$businessID = (int)$_SESSION['business_id'];

// Handle search query
$search_query = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Handle new album creation and image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_album'])) {
    $albumName = $conn->real_escape_string($_POST['albumName']);

    // Insert new album with LocID as NULL
    $sql_album = "INSERT INTO location_albums (LocID, AlbumName) VALUES (NULL, ?)";
    $stmt_album = $conn->prepare($sql_album);
    $stmt_album->bind_param("s", $albumName);
    if ($stmt_album->execute()) {
        $albumID = $stmt_album->insert_id;
        echo "<script>alert('Album $albumName created successfully with AlbumID $albumID!');</script>";
    } else {
        echo "<script>alert('Failed to create album: " . $stmt_album->error . "');</script>";
    }
    $stmt_album->close();

    // Handle image uploads for new album
    if (!empty($_FILES['floorplanImages']['name'][0])) {
        uploadImages($conn, $albumID, $albumName);
    }
}

// Handle adding images to existing album
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_images'])) {
    $albumID = (int)$_POST['albumID'];
    $albumName = $conn->real_escape_string($_POST['albumName']);

    if (!empty($_FILES['floorplanImages']['name'][0])) {
        uploadImages($conn, $albumID, $albumName);
    }
}

// Function to handle image uploads
function uploadImages($conn, $albumID, $albumName) {
    $uploadDir = '../uploads/floorplans/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $files = $_FILES['floorplanImages'];
    $fileCount = count($files['name']);

    // Get the highest sequence for this album's MapCode using full AlbumName
    $prefix = strtoupper($albumName); // e.g., "S13"
    $sql_last_mapcode = "SELECT MapCode FROM locationimage WHERE AlbumID = ? ORDER BY CAST(SUBSTRING(MapCode, INSTR(MapCode, '-') + 1) AS UNSIGNED) DESC LIMIT 1";
    $stmt_last_mapcode = $conn->prepare($sql_last_mapcode);
    $stmt_last_mapcode->bind_param("i", $albumID);
    $stmt_last_mapcode->execute();
    $last_mapcode_result = $stmt_last_mapcode->get_result();
    $last_mapcode = $last_mapcode_result->num_rows > 0 ? $last_mapcode_result->fetch_assoc()['MapCode'] : null;
    $stmt_last_mapcode->close();

    if ($last_mapcode && strpos($last_mapcode, $prefix) === 0) {
        $sequence = (int)substr($last_mapcode, strpos($last_mapcode, '-') + 1) + 1; // Increment from last sequence
    } else {
        $sequence = 1; // Start at 001 if no images exist for this album
    }

    for ($i = 0; $i < $fileCount; $i++) {
        $fileName = time() . '_' . basename($files['name'][$i]);
        $targetFile = $uploadDir . $fileName;
        $mapCode = sprintf("%s-%03d", $prefix, $sequence++);

        if (move_uploaded_file($files['tmp_name'][$i], $targetFile)) {
            $sql_image = "INSERT INTO locationimage (Filename, AlbumID, MapCode) VALUES (?, ?, ?)";
            $stmt_image = $conn->prepare($sql_image);
            $stmt_image->bind_param("sis", $fileName, $albumID, $mapCode);
            if ($stmt_image->execute()) {
                echo "<script>alert('Image $mapCode uploaded successfully to AlbumID $albumID!'); console.log('Saved: $mapCode, AlbumID: $albumID');</script>";
            } else {
                echo "<script>alert('Failed to register image $mapCode: " . $stmt_image->error . "');</script>";
            }
            $stmt_image->close();
        } else {
            echo "<script>alert('Failed to upload image: " . $files['name'][$i] . "');</script>";
        }
    }
}

// Fetch all albums and their images (not tied to a specific location), filtered by search query
$sql_albums = "SELECT AlbumID, AlbumName FROM location_albums WHERE LocID IS NULL AND AlbumName LIKE ?";
$search_param = "%$search_query%";
$stmt_albums = $conn->prepare($sql_albums);
$stmt_albums->bind_param("s", $search_param);
$stmt_albums->execute();
$albums_result = $stmt_albums->get_result();
$albums = $albums_result->fetch_all(MYSQLI_ASSOC);
$stmt_albums->close();

foreach ($albums as &$album) {
    $sql_images = "SELECT Filename, MapCode FROM locationimage WHERE AlbumID = ? ORDER BY MapCode";
    $stmt_images = $conn->prepare($sql_images);
    $stmt_images->bind_param("i", $album['AlbumID']);
    $stmt_images->execute();
    $images_result = $stmt_images->get_result();
    $album['images'] = $images_result->fetch_all(MYSQLI_ASSOC);
    $stmt_images->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Floorplan</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); position: relative; }
        h2 { color: #023047; }
        label { display: block; margin: 10px 0 5px; color: #023047; }
        input[type="text"], input[type="file"] { width: 100%; padding: 8px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 5px; }
        button { padding: 10px 20px; background: #ff7200; color: #fff; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #ff8c00; }
        .album-list { margin-top: 20px; }
        .album { border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; border-radius: 5px; }
        .album img { max-width: 100px; margin: 5px; }
        .button-group { display: flex; justify-content: space-between; margin-top: 20px; }
        .button-group button { width: 48%; padding: 10px; background-color: #ff7200; color: #fff; border: none; border-radius: 5px; cursor: pointer; }
        .search-container { position: absolute; top: 20px; right: 20px; }
        .search-container input[type="text"] { width: 200px; padding: 8px; border: 1px solid #ddd; border-radius: 5px; }
        .search-container button { padding: 8px 16px; margin-left: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Register Floorplan</h2>

        <!-- Search Field in Upper Right -->
        <div class="search-container">
            <form method="GET" action="floorplan.php">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search albums...">
                <button type="submit">Search</button>
            </form>
        </div>

        <!-- New Album Creation Form -->
        <form method="POST" action="floorplan.php" enctype="multipart/form-data">
            <label for="albumName">Album Name (e.g., S6)</label>
            <input type="text" id="albumName" name="albumName" required placeholder="e.g., S6">
            
            <label for="floorplanImages">Upload Floorplan Images</label>
            <input type="file" id="floorplanImages" name="floorplanImages[]" multiple accept="image/*">
            
            <button type="submit" name="create_album">Create Album & Upload</button>
        </form>

        <!-- Display Existing Albums -->
        <div class="album-list">
            <h3>Existing Floorplan Albums</h3>
            <?php if (empty($albums)): ?>
                <p>No albums registered yet<?php echo $search_query ? " matching '$search_query'" : ''; ?>.</p>
            <?php else: ?>
                <?php foreach ($albums as $album): ?>
                    <div class="album">
                        <h4><?php echo htmlspecialchars($album['AlbumName']); ?></h4>
                        <div>
                            <?php if (empty($album['images'])): ?>
                                <p>No images in this album.</p>
                            <?php else: ?>
                                <?php foreach ($album['images'] as $image): ?>
                                    <div>
                                        <img src="../uploads/floorplans/<?php echo htmlspecialchars($image['Filename']); ?>" alt="<?php echo htmlspecialchars($image['MapCode']); ?>">
                                        <p>Map Code: <?php echo htmlspecialchars($image['MapCode']); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <!-- Form to Add More Images to This Album -->
                            <form method="POST" action="floorplan.php" enctype="multipart/form-data">
                                <input type="hidden" name="albumID" value="<?php echo $album['AlbumID']; ?>">
                                <input type="hidden" name="albumName" value="<?php echo htmlspecialchars($album['AlbumName']); ?>">
                                <label for="floorplanImages_<?php echo $album['AlbumID']; ?>">Add More Images</label>
                                <input type="file" id="floorplanImages_<?php echo $album['AlbumID']; ?>" name="floorplanImages[]" multiple accept="image/*">
                                <button type="submit" name="add_images">Upload</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="button-group">
            <button type="button" onclick="window.location.href='admin_dashboard.php';">Back</button>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>