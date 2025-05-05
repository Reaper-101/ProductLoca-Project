<?php
// Include database connection
include 'conn/conn.php';
session_start();

// Check if the product ID is passed in the URL
if (isset($_GET['id'])) {
    $productID = intval($_GET['id']);

    // Fetch the product details using the product ID
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
        echo "<script>alert('Product not found or is inactive.'); window.location.href = 'online.php';</script>";
        exit();
    }
    $stmt->close();
} else {
    // If no product ID is passed, redirect back to the search page
    echo "<script>alert('No product selected.'); window.location.href = 'online.php';</script>";
    exit();
}

// Retrieve search query and other parameters if available
$search_query = isset($_GET['search_query']) ? $_GET['search_query'] : '';
$business_id = isset($_GET['business_id']) ? $_GET['business_id'] : '';
$filter_color = isset($_GET['filter_color']) ? $_GET['filter_color'] : '';
$filter_brand = isset($_GET['filter_brand']) ? $_GET['filter_brand'] : '';
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : '';
$filter_price_min = isset($_GET['filter_price_min']) ? $_GET['filter_price_min'] : '';
$filter_price_max = isset($_GET['filter_price_max']) ? $_GET['filter_price_max'] : '';

// Build the back URL with all parameters
$back_url = 'online.php';
$params = [];
if ($search_query) {
    $params[] = "search_query=" . urlencode($search_query);
}
if ($business_id) {
    $params[] = "business_id=" . urlencode($business_id);
}
if ($filter_color) {
    $params[] = "filter_color=" . urlencode($filter_color);
}
if ($filter_brand) {
    $params[] = "filter_brand=" . urlencode($filter_brand);
}
if ($filter_type) {
    $params[] = "filter_type=" . urlencode($filter_type);
}
if ($filter_price_min) {
    $params[] = "filter_price_min=" . urlencode($filter_price_min);
}
if ($filter_price_max) {
    $params[] = "filter_price_max=" . urlencode($filter_price_max);
}
if (!empty($params)) {
    $back_url .= '?' . implode('&', $params);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Details - <?php echo htmlspecialchars($product['Prod_name']); ?></title>
    <link rel="stylesheet" href="css/index_style.css?v=<?php echo time(); ?>">
    <style>
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: rgba(255, 255, 255, 0.04);
            border-radius: 10px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(5px);
            color: #fff;
        }
        .product-details {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
        }
        .product-details img {
            width: 300px;
            height: 300px;
            object-fit: cover;
            border-radius: 10px;
        }
        .product-info {
            flex: 1;
        }
        .product-info h2 {
            color: #fff;
            margin-bottom: 10px;
        }
        .product-info p {
            color: #ddd;
            margin: 8px 0;
        }
        .price {
            font-size: 24px;
            color: #ff7200;
        }
        .button-group {
            margin-top: 20px;
            display: flex;
            justify-content: flex-start;
        }
        .button-group a {
            background-color: #ff7200;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            text-align: center;
        }
        .button-group a:hover {
            background-color: #ff8c00;
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

            <!-- Back to Search button only (no Locate button) -->
            <div class="button-group">
                <a href="<?php echo $back_url; ?>">Back to Search</a>
            </div>
        </div>
    </div>
</body>
</html>