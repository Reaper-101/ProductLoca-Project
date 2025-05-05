<?php
session_start();
include '../conn/conn.php'; // Include the database connection

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.html");
    exit();
}

// Get the store ID from the URL
if (isset($_GET['id'])) {
    $storeID = $_GET['id'];
} else {
    echo "<script>alert('Invalid Store ID'); window.location.href = 'manage_stores.php';</script>";
    exit();
}

// Fetch store details
$sql_store = "SELECT * FROM store WHERE StoreID = '$storeID'";
$result_store = $conn->query($sql_store);

if ($result_store->num_rows > 0) {
    $store = $result_store->fetch_assoc();
    // Fetch associated images
    $sql_images = "SELECT ImagePath FROM store_images WHERE StoreID = '$storeID'";
    $images_result = $conn->query($sql_images);
    $images = [];
    while ($img = $images_result->fetch_assoc()) {
        $images[] = $img['ImagePath'];
    }
} else {
    echo "<script>alert('Store not found'); window.location.href = 'manage_stores.php';</script>";
    exit();
}

// Handle form submission for updating the store
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $storeBrandName = $conn->real_escape_string($_POST['storeBrandName']);
    $storeDescription = $conn->real_escape_string($_POST['storeDescription']);
    $status = $conn->real_escape_string($_POST['status']); // Get the selected status

    // Update the store details
    $sql_update = "UPDATE store 
                   SET StoreBrandName='$storeBrandName', 
                       StoreDescription='$storeDescription', 
                       status='$status' 
                   WHERE StoreID='$storeID'";
    if ($conn->query($sql_update) === TRUE) {
        // Handle multiple image uploads
        if (!empty($_FILES['storeImages']['name'][0])) {
            foreach ($_FILES['storeImages']['name'] as $i => $name) {
                if ($_FILES['storeImages']['error'][$i] === UPLOAD_ERR_OK) {
                    $target_dir = "../uploads/";
                    $imageFilename = basename($_FILES['storeImages']['name'][$i]);
                    $target_file = $target_dir . $imageFilename;

                    // Move the uploaded file to the target directory
                    if (move_uploaded_file($_FILES['storeImages']['tmp_name'][$i], $target_file)) {
                        // Insert file name into the store_images table
                        $sql_image = "INSERT INTO store_images (StoreID, ImagePath) VALUES ('$storeID', '$target_file')";
                        $conn->query($sql_image);
                    }
                }
            }
        }
        echo "<script>alert('Store updated successfully!'); window.location.href = 'manage_stores.php';</script>";
    } else {
        echo "<script>alert('Error updating store: " . $conn->error . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Store</title>
    <style>
        body { font-family: Arial, sans-serif; background: #ffff; }
        .container { width: 600px; margin: 50px auto; background: #023047;  padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        .container label {color: #ffff;}
        h2 { text-align: center; color: #ff7200; }
        input[type="text"], input[type="file"], select, textarea { width: 100%; padding: 10px; background: #ffff; margin: 10px 0; border-radius: 5px; border: 1px solid #ccc; }
        .button-group { display: flex; justify-content: space-between; margin-top: 20px; }
        .button-group button { width: 48%; padding: 10px; background-color: #ff7200; color: #fff; border: none; border-radius: 5px; cursor: pointer; }
        .button-group button:hover { background-color: #ff8c00; }
        .image-preview img { max-width: 100%; max-height: 150px; margin-top: 10px; border-radius: 10px; border: 1px solid #ddd; display: inline-block; margin-right: 10px; }
        .image-preview p {color: #ffff;s}
    </style>
</head>
<body>

<div class="container">
    <h2>Edit Store</h2>

    <!-- Edit Store Form -->
    <form method="POST" enctype="multipart/form-data" action="edit_store.php?id=<?php echo $storeID; ?>">
        <label for="storeBrandName">Store Name</label>
        <input type="text" id="storeBrandName" name="storeBrandName" value="<?php echo htmlspecialchars($store['StoreBrandName']); ?>" required>

        <label for="storeDescription">Store Description</label>
        <textarea id="storeDescription" name="storeDescription" rows="4" required><?php echo htmlspecialchars($store['StoreDescription']); ?></textarea>

        <label for="storeImages">Store Images (optional, multiple)</label>
        <input type="file" id="storeImages" name="storeImages[]" accept="image/*" multiple>

        <!-- Display current image previews -->
        <div class="image-preview">
            <?php foreach ($images as $image): ?>
                <img src="../uploads/<?php echo htmlspecialchars($image); ?>" alt="Store Image">
            <?php endforeach; ?>
            <?php if (count($images) == 0): ?>
                <p>No images uploaded.</p>
            <?php endif; ?>
        </div>

        <label for="status">Status</label>
        <select id="status" name="status" required>
            <option value="active" <?php echo ($store['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
            <option value="inactive" <?php echo ($store['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
        </select>

        <!-- Back and Update buttons in one row -->
        <div class="button-group">
            <button type="button" onclick="window.location.href='manage_stores.php';">Back</button>
            <button type="submit">Update Store</button>
        </div>
    </form>
</div>

</body>
</html>
