<?php
session_start();
include '../conn/conn.php'; // Include the database connection

// Check if a product ID is provided
if (!isset($_GET['id'])) {
    echo "<script>alert('Product ID is missing.'); window.location.href = 'manage_product.php';</script>";
    exit();
}

$productID = $_GET['id'];

// Fetch product details
$sql_product = "SELECT p.*, s.StoreBrandName 
                FROM product p 
                JOIN store s ON p.StoreID = s.StoreID 
                WHERE p.ProdID = '$productID'";
$result_product = $conn->query($sql_product);

if ($result_product->num_rows > 0) {
    $product = $result_product->fetch_assoc();
} else {
    echo "<script>alert('Product not found.'); window.location.href = 'manage_product.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Product</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #ffff;
        }
        .container {
            max-width: 800px;
            height: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #023047;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .container p{
           color: #ffff;
        }
        h2 {
            color: #ff7200;
            text-align: center;
            margin-bottom: 20px;
        }
        .product-details p {
            font-size: 16px;
            margin: 10px 0;
        }
        .product-details img {
            max-width: 300px; /* Set a smaller max width */
            height: auto;
            display: block;
            margin: 10px auto;
            border-radius: 8px;
        }
        .button-group {
            text-align: center;
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
    <h2>Product Details</h2>
    <div class="product-details">
        <p><strong>Product Name:</strong> <?php echo htmlspecialchars($product['Prod_name']); ?></p>
        <p><strong>Type:</strong> <?php echo htmlspecialchars($product['Prod_type']); ?></p>
        <p><strong>Color:</strong> <?php echo htmlspecialchars($product['Prod_color']); ?></p>
        <p><strong>Description:</strong> <?php echo htmlspecialchars($product['Prod_description']); ?></p>
        <p><strong>Price:</strong> $<?php echo htmlspecialchars($product['Price']); ?></p>
        <p><strong>Brand:</strong> <?php echo htmlspecialchars($product['Brand']); ?></p>
        <p><strong>Store:</strong> <?php echo htmlspecialchars($product['StoreBrandName']); ?></p>
        <p><strong>Status:</strong> <?php echo ucfirst($product['status']); ?></p>
        <p><strong>Image:</strong></p>
        <img src="../uploads/<?php echo htmlspecialchars($product['Image']); ?>" alt="Product Image">
    </div>
    <div class="button-group">
        <button onclick="window.location.href='manage_product.php'">Back</button>
    </div>
</div>

</body>
</html>
