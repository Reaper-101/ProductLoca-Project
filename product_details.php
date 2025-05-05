<?php
// Include database connection
include 'conn/conn.php';
session_start();

// Check if kiosk is set up
if (!isset($_SESSION['kioskID']) || !isset($_SESSION['kioskNum']) || !isset($_SESSION['kioskCode']) || !isset($_SESSION['business_id'])) {
    echo "<script>alert('Kiosk not set up. Please configure it first.'); window.location.href = 'admin/setup_kiosk.php';</script>";
    exit();
}

$kioskID = (int)$_SESSION['kioskID'];
$businessID = (int)$_SESSION['business_id'];

// Check if the product ID is passed in the URL
if (isset($_GET['id'])) {
    $productID = intval($_GET['id']);

    // Fetch the product details using the product ID with a prepared statement
    $sql_product = "
        SELECT 
            p.ProdID, p.Prod_name, p.Prod_description, p.Price, p.Image, p.Prod_color, p.Brand, p.Prod_type,
            s.StoreBrandName, s.StoreID,
            b.BusinessName
        FROM product p
        LEFT JOIN store s ON p.StoreID = s.StoreID
        LEFT JOIN businessinfo b ON s.BusinessID = b.BusinessID
        WHERE p.ProdID = ? AND p.status = 'active' AND s.status = 'active' AND b.status = 'active'";

    $stmt = $conn->prepare($sql_product);
    $stmt->bind_param("i", $productID);
    $stmt->execute();
    $result_product = $stmt->get_result();

    // Check if the product exists
    if ($result_product->num_rows > 0) {
        $product = $result_product->fetch_assoc();
    } else {
        echo "<script>alert('Product not found or is inactive.'); window.location.href = 'index.php';</script>";
        exit();
    }
    $stmt->close();
} else {
    // If no product ID is passed, redirect back to the search page
    echo "<script>alert('No product selected.'); window.location.href = 'index.php';</script>";
    exit();
}

// Retrieve search query if available
$search_query = isset($_GET['search_query']) ? $_GET['search_query'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Details - <?php echo htmlspecialchars($product['Prod_name']); ?></title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1C2526 0%, #1B263B 100%);
            background-size: 200% 200%;
            animation: gradientShift 15s ease infinite;
            margin: 0;
            padding: 20px;
            color: #fff;
        }
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .main {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            width: 100%;
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 140, 0, 0.2);
        }
        .product-details {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            flex-wrap: wrap;
        }
        .product-image img {
            width: 300px;
            height: 300px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid rgba(255, 140, 0, 0.3);
        }
        .product-info {
            flex: 1;
        }
        .product-info h2 {
            color: #ff7200;
            margin-bottom: 15px;
            font-size: 24px;
        }
        .product-info p {
            color: #ddd;
            margin: 8px 0;
            font-size: 16px;
        }
        .price {
            font-size: 24px;
            color: #ff7200;
            font-weight: bold;
        }
        .button-group {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }
        .button-group a {
            background: linear-gradient(90deg, #1B263B 0%, #ff7200 100%);
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 25px;
            text-align: center;
            flex: 1;
            transition: transform 0.1s ease;
        }
        .button-group a:hover {
            transform: scale(1.02);
        }
    </style>
</head>
<body>
    <div class="main">
        <div class="container">
            <div class="product-details">
                <div class="product-image">
                    <?php if (!empty($product['Image'])) { ?>
                        <img src="uploads/<?php echo htmlspecialchars($product['Image']); ?>" alt="<?php echo htmlspecialchars($product['Prod_name']); ?>">
                    <?php } else { ?>
                        <img src="uploads/default.png" alt="Default Image">
                    <?php } ?>
                </div>
                <div class="product-info">
                    <h2><?php echo htmlspecialchars($product['Prod_name']); ?></h2>
                    <p><strong>Brand:</strong> <?php echo htmlspecialchars($product['Brand']); ?></p>
                    <p><strong>Color:</strong> <?php echo htmlspecialchars($product['Prod_color']); ?></p>
                    <p><strong>Type:</strong> <?php echo htmlspecialchars($product['Prod_type']); ?></p>
                    <p><strong>Description:</strong> <?php echo htmlspecialchars($product['Prod_description']); ?></p>
                    <p><strong>Store:</strong> <?php echo htmlspecialchars($product['StoreBrandName']); ?></p>
                    <p><strong>Business:</strong> <?php echo htmlspecialchars($product['BusinessName']); ?></p>
                    <p class="price"><strong>Price:</strong> ₱<?php echo number_format($product['Price'], 0); ?></p>
                </div>
            </div>

            <!-- Back to Search and Locate buttons -->
            <div class="button-group">
                <a href="index.php?search_query=<?php echo urlencode($search_query); ?>">Back to Search</a>
                <a href="locate.php?id=<?php echo $product['ProdID']; ?>&search_query=<?php echo urlencode($search_query); ?>">Locate</a>
            </div>
        </div>
    </div>
</body>
</html>