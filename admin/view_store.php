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
    $storeID = intval($_GET['id']);
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Store Details</title>
    <style>
        body { font-family: Arial, sans-serif; background: #ffff;}
        .container { width: 600px; margin: 50px auto; background: #023047; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        .container p { color: #ffff;}
        h2 { text-align: center; color: #ff7200; }
        .store-details img { width: 100%; max-width: 200px; height: auto; margin-top: 10px; display: inline-block; margin-right: 10px; }
        .back-button { margin-top: 20px; display: flex; justify-content: center; }
        .back-button a { background-color: #ff7200; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        .back-button a:hover { background-color: #ff8c00; }
    </style>
</head>
<body>

<div class="container">
    <h2>Store Details</h2>
    <div class="store-details">
        <p><strong>Store Name:</strong> <?php echo htmlspecialchars($store['StoreBrandName']); ?></p>
        <p><strong>Description:</strong> <?php echo htmlspecialchars($store['StoreDescription']); ?></p>
        <p><strong>Status:</strong> <?php echo ucfirst($store['status']); ?></p>
        
        <p><strong>Store Images:</strong></p>
        <?php if (count($images) > 0): ?>
            <?php foreach ($images as $image): ?>
                <img src="../uploads/<?php echo htmlspecialchars($image); ?>" alt="Store Image">
            <?php endforeach; ?>
        <?php else: ?>
            <p><em>No images available for this store.</em></p>
        <?php endif; ?>
    </div>

    <!-- Back to Manage Stores button -->
    <div class="back-button">
        <a href="manage_stores.php">Back to Manage Stores</a>
    </div>
</div>

</body>
</html>
