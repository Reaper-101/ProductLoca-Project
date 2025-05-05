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

// Handle form submission for adding a new store
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $storeName = $conn->real_escape_string($_POST['storeName']);
    $storeDescription = $conn->real_escape_string($_POST['storeDescription']);
    $status = $conn->real_escape_string($_POST['status']);

    // Insert into the store table
    $sql_store = "INSERT INTO store (StoreBrandName, StoreDescription, BusinessID, status) 
                  VALUES ('$storeName', '$storeDescription', '$businessID', '$status')";
    if ($conn->query($sql_store) === TRUE) {
        $newStoreID = $conn->insert_id; // Get the ID of the newly added store

        // Handle multiple image uploads
        if (isset($_FILES['storeImages']) && count($_FILES['storeImages']['name']) > 0) {
            for ($i = 0; $i < count($_FILES['storeImages']['name']); $i++) {
                if ($_FILES['storeImages']['error'][$i] === UPLOAD_ERR_OK) {
                    $target_dir = "../uploads/";
                    $imageFilename = basename($_FILES['storeImages']['name'][$i]);
                    $target_file = $target_dir . $imageFilename;

                    // Move the uploaded file to the target directory
                    if (move_uploaded_file($_FILES['storeImages']['tmp_name'][$i], $target_file)) {
                        // Insert file name into the store_images table
                        $sql_image = "INSERT INTO store_images (StoreID, ImagePath) VALUES ('$newStoreID', '$imageFilename')";
                        $conn->query($sql_image);
                    }
                }
            }
        }

        echo "<script>alert('Store added successfully!'); window.location.href = 'manage_stores.php';</script>";
    } else {
        echo "<script>alert('Error adding store: " . $conn->error . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Store</title>
    <style>
        body { font-family: Arial, sans-serif; background: #ffff; }
        .container { width: 600px; margin: 50px auto; background: #023047; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        h2 { text-align: center; color: #ffff; }
        .container label { color: #ffff }
        .container input[type="file"] { background: #ffff; }
        input[type="text"], textarea, select, input[type="file"] { width: 100%; padding: 10px; margin: 10px 0; border-radius: 5px; border: 1px solid #ccc; }
        .button-group { display: flex; justify-content: space-between; margin-top: 20px; }
        .button-group button { width: 48%; padding: 10px; background-color: #ff7200; color: #fff; border: none; border-radius: 5px; cursor: pointer; }
        .button-group button:hover { background-color: #ff8c00; }
    </style>
</head>
<body>

<div class="container">
    <h2>Add New Store</h2>
    <form method="POST" enctype="multipart/form-data">
        <label for="storeName">Store Name</label>
        <input type="text" id="storeName" name="storeName" required>

        <label for="storeDescription">Store Description</label>
        <textarea id="storeDescription" name="storeDescription" rows="4" required></textarea>

        <label for="status">Status</label>
        <select id="status" name="status" required>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>

        <label for="storeImages">Store Images (optional)</label>
        <input type="file" id="storeImages" name="storeImages[]" accept="image/*" multiple>

        <!-- Back and Add Store buttons in one row -->
        <div class="button-group">
            <button type="button" onclick="window.location.href='manage_stores.php';">Back</button>
            <button type="submit">Add Store</button>
        </div>
    </form>
</div>

</body>
</html>
