<?php
session_start();
include '../conn/conn.php'; // Include the database connection

// Check if the user is logged in and is a store manager
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'store_manager') {
    header("Location: ../login.html"); // Redirect to login if not a store manager
    exit();
}

// Get the product ID from the URL
if (isset($_GET['id'])) {
    $prodID = $conn->real_escape_string($_GET['id']);
} else {
    echo "<script>alert('Invalid Product ID'); window.location.href = 'manage_product.php';</script>";
    exit();
}

// Fetch the product details
$sql_product = "SELECT * FROM product WHERE ProdID = '$prodID'";
$result_product = $conn->query($sql_product);
if ($result_product->num_rows > 0) {
    $product = $result_product->fetch_assoc();
} else {
    echo "<script>alert('Product not found.'); window.location.href = 'manage_product.php';</script>";
    exit();
}

// Handle form submission for updating the product details
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $productName = $conn->real_escape_string($_POST['productName']);
    $productType = $conn->real_escape_string($_POST['productType']);
    $productColor = $conn->real_escape_string($_POST['productColor']);
    $productDescription = $conn->real_escape_string($_POST['productDescription']);
    $productPrice = $conn->real_escape_string($_POST['productPrice']);
    $productBrand = $conn->real_escape_string($_POST['productBrand']); // Updated Brand Field
    $productStatus = $conn->real_escape_string($_POST['status']);

    // Handle image upload (if a new image is uploaded)
    if (!empty($_FILES['productImage']['name'])) {
        $productImage = $_FILES['productImage']['name'];
        $target_dir = "../uploads/";
        $target_file = $target_dir . basename($productImage);

        if (move_uploaded_file($_FILES['productImage']['tmp_name'], $target_file)) {
            // Update product details with the new image
            $sql_update = "UPDATE product 
                           SET Prod_name='$productName', Prod_type='$productType', Prod_color='$productColor',
                               Prod_description='$productDescription', Price='$productPrice', Brand='$productBrand',
                               Image='$productImage', status='$productStatus' 
                           WHERE ProdID='$prodID'";
        } else {
            echo "<script>alert('Failed to upload image.');</script>";
        }
    } else {
        // Update product details without image change
        $sql_update = "UPDATE product 
                       SET Prod_name='$productName', Prod_type='$productType', Prod_color='$productColor',
                           Prod_description='$productDescription', Price='$productPrice', Brand='$productBrand',
                           status='$productStatus' 
                       WHERE ProdID='$prodID'";
    }

    // Execute the update query
    if ($conn->query($sql_update) === TRUE) {
        echo "<script>alert('Product updated successfully!'); window.location.href = 'manage_product.php';</script>";
    } else {
        echo "<script>alert('Error updating product: " . $conn->error . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
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
        .container {
            color: #ffff;
        }   
        h2 {
            text-align: center;
            color: #ff7200;
        }
        input[type="text"], input[type="file"], textarea, input[type="number"], select {
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
    <h2>Edit Product</h2>
    <form method="POST" enctype="multipart/form-data" action="edit_product.php?id=<?php echo $prodID; ?>">
        <label for="productName">Product Name</label>
        <input type="text" id="productName" name="productName" value="<?php echo htmlspecialchars($product['Prod_name']); ?>" required>

        <label for="productType">Product Type</label>
        <input type="text" id="productType" name="productType" value="<?php echo htmlspecialchars($product['Prod_type']); ?>" required>

        <label for="productColor">Product Color</label>
        <input type="text" id="productColor" name="productColor" value="<?php echo htmlspecialchars($product['Prod_color']); ?>" required>

        <label for="productDescription">Product Description</label>
        <textarea id="productDescription" name="productDescription" rows="4" required><?php echo htmlspecialchars($product['Prod_description']); ?></textarea>

        <label for="productPrice">Product Price</label>
        <input type="number" id="productPrice" name="productPrice" step="0.01" value="<?php echo htmlspecialchars($product['Price']); ?>" required>

        <label for="productBrand">Product Brand</label> <!-- Brand Field for Editing -->
        <input type="text" id="productBrand" name="productBrand" value="<?php echo htmlspecialchars($product['Brand']); ?>" required>

        <label for="productImage">Upload New Product Image</label>
        <input type="file" id="productImage" name="productImage" accept="image/*">
        <p>Current Image: 
        <?php if ($product['Image']) { ?>
            <img src="../uploads/<?php echo htmlspecialchars($product['Image']); ?>" alt="Product Image" width="100">
        <?php } else { ?>
            <span>No image uploaded</span>
        <?php } ?>
        </p>

        <label for="status">Status</label>
        <select id="status" name="status" required>
            <option value="active" <?php if ($product['status'] == 'active') echo 'selected'; ?>>Active</option>
            <option value="inactive" <?php if ($product['status'] == 'inactive') echo 'selected'; ?>>Inactive</option>
        </select>

        <!-- Buttons in one row -->
        <div class="button-group">
            <button type="button" onclick="window.location.href='manage_product.php';">Back</button>
            <button type="submit">Update Product</button>
        </div>
    </form>
</div>
</body>
</html>
