<?php
session_start();
include '../conn/conn.php'; // Include the database connection

// Check if the user is logged in as a store manager
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'store_manager') {
    header("Location: ../login.html");
    exit();
}

// Fetch the StoreID for the logged-in store manager
$user_id = $_SESSION['user_id'];
$sql_store = "SELECT StoreID FROM UserAccount WHERE UserAccountID = '$user_id'";
$result_store = $conn->query($sql_store);

if ($result_store->num_rows > 0) {
    $store = $result_store->fetch_assoc();
    $currentStoreID = $store['StoreID'];

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $productName = $conn->real_escape_string($_POST['productName']);
        $productType = $conn->real_escape_string($_POST['productType']);
        $productColor = $conn->real_escape_string($_POST['productColor']);
        $productDescription = $conn->real_escape_string($_POST['productDescription']);
        $productPrice = $conn->real_escape_string($_POST['productPrice']);
        $productBrand = $conn->real_escape_string($_POST['productBrand']);
        
        // Optional image upload handling
        $productImage = null;
        if (isset($_FILES['productImage']['name']) && $_FILES['productImage']['name'] != '') {
            $target_dir = "../uploads/";
            $productImage = basename($_FILES['productImage']['name']);
            $target_file = $target_dir . $productImage;

            if (!move_uploaded_file($_FILES['productImage']['tmp_name'], $target_file)) {
                echo "<script>alert('Failed to upload image.');</script>";
                $productImage = null; // Reset if upload fails
            }
        }

        // Insert into the product table with or without image
        $sql_add_product = "INSERT INTO product (Prod_name, Prod_type, Prod_color, Prod_description, Price, Image, StoreID, Brand, status) 
                            VALUES ('$productName', '$productType', '$productColor', '$productDescription', '$productPrice', 
                                    " . ($productImage ? "'$productImage'" : "NULL") . ", '$currentStoreID', '$productBrand', 'active')";
        
        if ($conn->query($sql_add_product) === TRUE) {
            echo "<script>alert('Product added successfully!'); window.location.href = 'manage_product.php';</script>";
        } else {
            echo "<script>alert('Error adding product: " . $conn->error . "');</script>";
        }
    }
} else {
    echo "<script>alert('No store associated with this store manager.'); window.location.href = 'store_manager_dashboard.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #ffff;
        }
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #023047;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .container label{
           color: #ffff;
        }
        h2 {
            color: #ff7200;
            text-align: center;
            margin-bottom: 20px;
        }
        input[type="text"], input[type="file"], textarea, input[type="number"] {
            width: 100%;
            padding: 8px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .button-group {
            display: flex;
            justify-content: space-between;
        }
        .button-group button {
            padding: 10px 20px;
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
    <h2>Add New Product</h2>
    <form method="POST" enctype="multipart/form-data">
        <label for="productName">Product Name</label>
        <input type="text" id="productName" name="productName" required>

        <label for="productType">Product Type</label>
        <input type="text" id="productType" name="productType" required>

        <label for="productColor">Product Color</label>
        <input type="text" id="productColor" name="productColor" required>

        <label for="productDescription">Product Description</label>
        <textarea id="productDescription" name="productDescription" required></textarea>

        <label for="productPrice">Product Price</label>
        <input type="number" id="productPrice" name="productPrice" step="0.01" required>

        <label for="productBrand">Product Brand</label>
        <input type="text" id="productBrand" name="productBrand" required>

        <label for="productImage">Product Image (optional)</label>
        <input type="file" id="productImage" name="productImage" accept="image/*">

        <div class="button-group">
            <button type="button" onclick="window.location.href='manage_product.php';">Back</button>
            <button type="submit">Add Product</button>
        </div>
    </form>
</div>

</body>
</html>
