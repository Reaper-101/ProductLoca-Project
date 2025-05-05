<?php
date_default_timezone_set('Asia/Manila'); // Local time zone
session_start();
include 'conn/conn.php'; // Include the database connection

// Clear session search data if coming back to the main dashboard
if (!isset($_GET['search_query'])) {
    unset($_SESSION['search_query']);
}

// Fetch all businesses for the dropdown
$business_query = "SELECT BusinessID, BusinessName FROM businessinfo WHERE Visibility = 1 AND status = 'active'";
$business_result = $conn->query($business_query);

// Handle search query counting (updated to use prepared statements for security)
if (isset($_GET['search_query']) && !empty($_GET['search_query'])) {
    $search_query = $conn->real_escape_string($_GET['search_query']);
    $current_date = date('Y-m-d');
    $current_time = date('H:i:s');

    $check_query = "SELECT ID, SearchCount FROM searchanalytics WHERE SearchQuery = ? AND SearchDate = ?";
    $stmt_check = $conn->prepare($check_query);
    $stmt_check->bind_param("ss", $search_query, $current_date);
    $stmt_check->execute();
    $check_result = $stmt_check->get_result();

    if ($check_result->num_rows > 0) {
        $row = $check_result->fetch_assoc();
        $search_id = $row['ID'];
        $new_count = $row['SearchCount'] + 1;

        $update_query = "UPDATE searchanalytics SET SearchCount = ?, SearchTime = ? WHERE ID = ?";
        $stmt_update = $conn->prepare($update_query);
        $stmt_update->bind_param("isi", $new_count, $current_time, $search_id);
        $stmt_update->execute();
        $stmt_update->close();
    } else {
        $insert_query = "INSERT INTO searchanalytics (SearchQuery, SearchDate, SearchCount, SearchTime) VALUES (?, ?, 1, ?)";
        $stmt_insert = $conn->prepare($insert_query);
        $stmt_insert->bind_param("sss", $search_query, $current_date, $current_time);
        $stmt_insert->execute();
        $stmt_insert->close();
    }
    $stmt_check->close();
}

// Fetch unique colors, brands, and product types for filters (same as index.php)
$business_filter_for_filters = '';
if (isset($_GET['business_id']) && !empty($_GET['business_id'])) {
    $business_id = intval($_GET['business_id']);
    $business_filter_for_filters = "AND StoreID IN (SELECT StoreID FROM store WHERE BusinessID = $business_id AND status = 'active')";
}

$color_query = "SELECT DISTINCT Prod_color FROM product WHERE status = 'active' $business_filter_for_filters ORDER BY Prod_color";
$stmt_colors = $conn->prepare($color_query);
$stmt_colors->execute();
$colors_result = $stmt_colors->get_result();
$colors = $colors_result->fetch_all(MYSQLI_ASSOC);
$stmt_colors->close();

$brand_query = "SELECT DISTINCT Brand FROM product WHERE status = 'active' $business_filter_for_filters ORDER BY Brand";
$stmt_brands = $conn->prepare($brand_query);
$stmt_brands->execute();
$brands_result = $stmt_brands->get_result();
$brands = $brands_result->fetch_all(MYSQLI_ASSOC);
$stmt_brands->close();

$type_query = "SELECT DISTINCT Prod_type FROM product WHERE status = 'active' $business_filter_for_filters ORDER BY Prod_type";
$stmt_types = $conn->prepare($type_query);
$stmt_types->execute();
$types_result = $stmt_types->get_result();
$types = $types_result->fetch_all(MYSQLI_ASSOC);
$stmt_types->close();

// Get filter values from GET parameters (same as index.php)
$filter_color = isset($_GET['filter_color']) && !empty($_GET['filter_color']) ? $conn->real_escape_string($_GET['filter_color']) : '';
$filter_brand = isset($_GET['filter_brand']) && !empty($_GET['filter_brand']) ? $conn->real_escape_string($_GET['filter_brand']) : '';
$filter_type = isset($_GET['filter_type']) && !empty($_GET['filter_type']) ? $conn->real_escape_string($_GET['filter_type']) : '';
$filter_price_min = isset($_GET['filter_price_min']) && is_numeric($_GET['filter_price_min']) ? (int)$_GET['filter_price_min'] : '';
$filter_price_max = isset($_GET['filter_price_max']) && is_numeric($_GET['filter_price_max']) ? (int)$_GET['filter_price_max'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Loca - Online Product Search</title>
    <link rel="stylesheet" href="css/index_style.css?v=<?php echo time(); ?>">
    <style>
        /* General Glassmorphism Effect */
        .glass-effect {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(5px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Header Styles */
        .header {
            padding: 15px 20px;
            text-align: center;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Navbar Styles - Single Row Layout */
        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 20px;
            flex-wrap: wrap;
        }
        .navbar .icon {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .navbar .logo-img {
            height: 40px;
        }
        .navbar .menu ul {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 20px;
        }
        .navbar .menu ul li a {
            color: #fff;
            text-decoration: none;
            font-size: 16px;
        }
        .navbar .menu ul li a:hover {
            color: #ff8c00;
        }
        .navbar .business-dropdown {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .navbar .business-dropdown label {
            font-size: 16px;
            color: #fff;
        }
        .navbar .business-dropdown select {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 16px;
            border: 1px solid #ddd;
            background: #ff8c00;
            color: #fff;
        }
        .navbar .business-dropdown option {
            background: #ff8c00;
            color: #fff;
        }
        .navbar .search {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .navbar .search-container {
            display: flex;
            align-items: center;
            position: relative;
        }
        .navbar .back-arrow {
            display: none;
            color: #fff;
            text-decoration: none;
            font-size: 20px;
            margin-right: 10px;
        }
        <?php if (isset($_GET['search_query']) && !empty($_GET['search_query'])): ?>
        .navbar .back-arrow {
            display: inline-block;
        }
        <?php endif; ?>
        .navbar .srch {
            padding: 8px 12px;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-size: 16px;
            width: 200px;
        }
        .navbar .btn {
            padding: 8px 15px;
            border-radius: 5px;
            border: none;
            background: #ff8c00;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
        }
        .navbar .btn:hover {
            background: #e07b00;
        }

        /* Filter Section Styles */
        .filter-section {
            padding: 15px 20px;
        }
        .filter-section h3 {
            color: #fff;
            margin: 0 0 10px;
            font-size: 20px;
        }
        .filter-section form {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-section label {
            color: #fff;
            font-size: 14px;
        }
        .filter-section select,
        .filter-section input[type="text"] {
            padding: 5px 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-size: 14px;
        }
        .filter-section button {
            padding: 5px 15px;
            border-radius: 5px;
            border: none;
            background: #ff8c00;
            color: #fff;
            font-size: 14px;
            cursor: pointer;
        }
        .filter-section button:hover {
            background: #e07b00;
        }

        /* Product Cards - Horizontal Scroll Layout */
        .search-results {
            padding: 20px;
        }
        .search-results h2 {
            color: #fff;
            margin-bottom: 15px;
            font-size: 24px;
        }
        .product-card {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            padding-bottom: 10px;
        }
        .product-card::-webkit-scrollbar {
            height: 8px;
        }
        .product-card::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }
        .product-item {
            flex: 0 0 250px;
            background: rgba(255, 255, 255, 0.04);
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(5px);
        }
        .product-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
        }
        .product-item h3 {
            color: #fff;
            font-size: 18px;
            margin: 10px 0;
        }
        .product-item p {
            color: #ddd;
            font-size: 14px;
            margin: 5px 0;
        }
        /* Footer styling */
        footer {
            text-align: center;
            padding: 20px;
            background: #023047;
            color: #fff;
            margin-top: 20px;
            border-radius: 10px;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
        }
        footer p {
            margin: 5px 0;
            font-size: 14px;
        }
        footer a {
            color: #ff7200;
            text-decoration: none;
        }
        footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="main">

        <!-- Navbar (Single Row) -->
        <div class="navbar glass-effect">
            <div class="icon">
               <img src="image/ourlogo.png" alt="Logo 1" class="logo-img">
            </div>
            <div class="menu">
                <ul>
                    <li><a href="about.html">ABOUT</a></li>
                </ul>
            </div>
            <div class="business-dropdown">
                <form id="businessForm" action="online.php" method="GET">
                    <label for="business">Choose Business:</label>
                    <select name="business_id" id="business" onchange="document.getElementById('businessForm').submit()">
                        <option value="">All Businesses</option>
                        <?php
                        if ($business_result->num_rows > 0) {
                            while ($business = $business_result->fetch_assoc()) {
                                $selected = isset($_GET['business_id']) && $_GET['business_id'] == $business['BusinessID'] ? 'selected' : '';
                                echo "<option value='{$business['BusinessID']}' $selected>{$business['BusinessName']}</option>";
                            }
                        }
                        ?>
                    </select>
                    <?php if (isset($_GET['search_query'])): ?>
                        <input type="hidden" name="search_query" value="<?php echo htmlspecialchars($_GET['search_query']); ?>">
                    <?php endif; ?>
                    <?php if (isset($_GET['filter_color'])): ?>
                        <input type="hidden" name="filter_color" value="<?php echo htmlspecialchars($_GET['filter_color']); ?>">
                    <?php endif; ?>
                    <?php if (isset($_GET['filter_brand'])): ?>
                        <input type="hidden" name="filter_brand" value="<?php echo htmlspecialchars($_GET['filter_brand']); ?>">
                    <?php endif; ?>
                    <?php if (isset($_GET['filter_type'])): ?>
                        <input type="hidden" name="filter_type" value="<?php echo htmlspecialchars($_GET['filter_type']); ?>">
                    <?php endif; ?>
                    <?php if (isset($_GET['filter_price_min'])): ?>
                        <input type="hidden" name="filter_price_min" value="<?php echo htmlspecialchars($_GET['filter_price_min']); ?>">
                    <?php endif; ?>
                    <?php if (isset($_GET['filter_price_max'])): ?>
                        <input type="hidden" name="filter_price_max" value="<?php echo htmlspecialchars($_GET['filter_price_max']); ?>">
                    <?php endif; ?>
                </form>
            </div>
            <div class="search">
                <form id="searchForm" action="online.php" method="GET">
                    <div class="search-container">
                        <a href="online.php" class="back-arrow">←</a>
                        <input class="srch" type="search" name="search_query" placeholder="Search Product or Price (e.g., 500 t-shirt)" value="<?php echo isset($_GET['search_query']) ? htmlspecialchars($_GET['search_query']) : ''; ?>">
                    </div>
                    <button type="submit" class="btn">Search</button>
                    <?php if (isset($_GET['business_id'])): ?>
                        <input type="hidden" name="business_id" value="<?php echo htmlspecialchars($_GET['business_id']); ?>">
                    <?php endif; ?>
                    <?php if (isset($_GET['filter_color'])): ?>
                        <input type="hidden" name="filter_color" value="<?php echo htmlspecialchars($_GET['filter_color']); ?>">
                    <?php endif; ?>
                    <?php if (isset($_GET['filter_brand'])): ?>
                        <input type="hidden" name="filter_brand" value="<?php echo htmlspecialchars($_GET['filter_brand']); ?>">
                    <?php endif; ?>
                    <?php if (isset($_GET['filter_type'])): ?>
                        <input type="hidden" name="filter_type" value="<?php echo htmlspecialchars($_GET['filter_type']); ?>">
                    <?php endif; ?>
                    <?php if (isset($_GET['filter_price_min'])): ?>
                        <input type="hidden" name="filter_price_min" value="<?php echo htmlspecialchars($_GET['filter_price_min']); ?>">
                    <?php endif; ?>
                    <?php if (isset($_GET['filter_price_max'])): ?>
                        <input type="hidden" name="filter_price_max" value="<?php echo htmlspecialchars($_GET['filter_price_max']); ?>">
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="content">
            <!-- Filter Section -->
            <div class="filter-section glass-effect">
                <h3>Filters</h3>
                <form method="GET" action="online.php">
                    <?php if (isset($_GET['search_query'])): ?>
                        <input type="hidden" name="search_query" value="<?php echo htmlspecialchars($_GET['search_query']); ?>">
                    <?php endif; ?>
                    <?php if (isset($_GET['business_id'])): ?>
                        <input type="hidden" name="business_id" value="<?php echo htmlspecialchars($_GET['business_id']); ?>">
                    <?php endif; ?>
                    <label for="filter_color">Color:</label>
                    <select name="filter_color" id="filter_color">
                        <option value="">All Colors</option>
                        <?php foreach ($colors as $color): ?>
                            <option value="<?php echo htmlspecialchars($color['Prod_color']); ?>" <?php echo $filter_color === $color['Prod_color'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($color['Prod_color']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="filter_brand">Brand:</label>
                    <select name="filter_brand" id="filter_brand">
                        <option value="">All Brands</option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?php echo htmlspecialchars($brand['Brand']); ?>" <?php echo $filter_brand === $brand['Brand'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($brand['Brand']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="filter_type">Product Type:</label>
                    <select name="filter_type" id="filter_type">
                        <option value="">All Types</option>
                        <?php foreach ($types as $type): ?>
                            <option value="<?php echo htmlspecialchars($type['Prod_type']); ?>" <?php echo $filter_type === $type['Prod_type'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['Prod_type']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="filter_price_min">Price Range:</label>
                    <input type="text" name="filter_price_min" id="filter_price_min" class="price-input" placeholder="From" value="<?php echo $filter_price_min !== '' ? htmlspecialchars($filter_price_min) : ''; ?>">
                    <input type="text" name="filter_price_max" id="filter_price_max" class="price-input" placeholder="To" value="<?php echo $filter_price_max !== '' ? htmlspecialchars($filter_price_max) : ''; ?>">

                    <button type="submit">Apply Filters</button>
                </form>
                <!-- Disclaimer and Contact Info -->
            <footer>
                <p><strong>Disclaimer:</strong> Some products displayed may not be available at all times due to stock or other factors.</p>
                <p>For inquiries or to check availability, please contact us at: <a href="mailto:support@productloca.com">support@productloca.com</a> or call <strong>+63 912 345 6789</strong>.</p>
            </footer>
            </div>

            <!-- Display products -->
            <?php
            $business_filter = '';
            if (isset($_GET['business_id']) && !empty($_GET['business_id'])) {
                $business_id = intval($_GET['business_id']);
                $business_filter = "AND b.BusinessID = $business_id";
            }

            if (isset($_GET['search_query']) && !empty($_GET['search_query'])) {
                $search_query = $conn->real_escape_string($_GET['search_query']);
                $_SESSION['search_query'] = $search_query;

                $conditions = [];
                $price_conditions = [];
                $text_conditions = [];
                $keywords = explode(" ", $search_query);

                foreach ($keywords as $word) {
                    $word = $conn->real_escape_string(strtolower($word));
                    if (is_numeric($word)) {
                        $price = (float)$word;
                        $price_conditions[] = "p.Price BETWEEN $price AND 100000";
                    } else {
                        $text_conditions[] = "(LOWER(p.Prod_name) LIKE '%$word%' OR LOWER(p.Prod_description) LIKE '%$word%' OR LOWER(p.Prod_color) LIKE '%$word%' OR LOWER(p.Prod_type) LIKE '%$word%' OR LOWER(p.Brand) LIKE '%$word%')";
                    }
                }

                if (!empty($text_conditions)) {
                    $conditions[] = "(" . implode(" AND ", $text_conditions) . ")";
                }
                if (!empty($price_conditions)) {
                    $conditions[] = "(" . implode(" AND ", $price_conditions) . ")";
                } else {
                    $conditions[] = "p.Price BETWEEN 1 AND 100000";
                }

                if ($filter_color) {
                    $conditions[] = "p.Prod_color = '$filter_color'";
                }
                if ($filter_brand) {
                    $conditions[] = "p.Brand = '$filter_brand'";
                }
                if ($filter_type) {
                    $conditions[] = "p.Prod_type = '$filter_type'";
                }
                if ($filter_price_min !== '' && $filter_price_max !== '' && $filter_price_min <= $filter_price_max) {
                    $conditions[] = "p.Price BETWEEN $filter_price_min AND $filter_price_max";
                } elseif ($filter_price_min !== '') {
                    $conditions[] = "p.Price >= $filter_price_min";
                } elseif ($filter_price_max !== '') {
                    $conditions[] = "p.Price <= $filter_price_max";
                }

                $where_clause = implode(" AND ", $conditions);
                $query = "SELECT p.ProdID, p.Prod_name, p.Prod_type, p.Prod_description, p.Price, p.Image, p.Prod_color, p.Brand, s.StoreBrandName, b.BusinessName
                          FROM product p
                          JOIN store s ON p.StoreID = s.StoreID
                          JOIN businessinfo b ON s.BusinessID = b.BusinessID
                          WHERE $where_clause $business_filter AND b.Visibility = 1 AND s.status = 'active' AND p.status = 'active'";
                $stmt = $conn->prepare($query);
                $stmt->execute();
                $result = $stmt->get_result();

                echo '<div class="search-results"><h2>Search Results for "' . htmlspecialchars($search_query) . '"</h2><div class="product-card">';

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        // Link to product_details_online.php with all parameters
                        $details_url = "product_details_online.php?id=" . $row['ProdID'];
                        $details_url .= "&search_query=" . urlencode($search_query);
                        if (isset($_GET['business_id'])) {
                            $details_url .= "&business_id=" . urlencode($_GET['business_id']);
                        }
                        if (isset($_GET['filter_color'])) {
                            $details_url .= "&filter_color=" . urlencode($_GET['filter_color']);
                        }
                        if (isset($_GET['filter_brand'])) {
                            $details_url .= "&filter_brand=" . urlencode($_GET['filter_brand']);
                        }
                        if (isset($_GET['filter_type'])) {
                            $details_url .= "&filter_type=" . urlencode($_GET['filter_type']);
                        }
                        if (isset($_GET['filter_price_min'])) {
                            $details_url .= "&filter_price_min=" . urlencode($_GET['filter_price_min']);
                        }
                        if (isset($_GET['filter_price_max'])) {
                            $details_url .= "&filter_price_max=" . urlencode($_GET['filter_price_max']);
                        }

                        echo '<div class="product-item">';
                        echo '<a href="' . $details_url . '"><img src="uploads/' . htmlspecialchars($row['Image']) . '" alt="Product Image"></a>';
                        echo '<h3>' . htmlspecialchars($row['Prod_name']) . '</h3>';
                        echo '<p>Brand: ' . htmlspecialchars($row['Brand']) . '</p>';
                        echo '<p>Type: ' . htmlspecialchars($row['Prod_type']) . '</p>';
                        echo '<p>Price: ₱' . number_format($row['Price'], 0) . '</p>';
                        echo '<p>' . htmlspecialchars($row['Prod_description']) . '</p>';
                        echo '<p>Color: ' . htmlspecialchars($row['Prod_color']) . '</p>';
                        echo '<p>Store: ' . htmlspecialchars($row['StoreBrandName']) . '</p>';
                        echo '<p>Business: ' . htmlspecialchars($row['BusinessName']) . '</p>';
                        echo '</div>';
                    }
                } else {
                    echo '<p>No products found matching your search.</p>';
                }
                echo '</div></div>';
                $stmt->close();
            } else {
                $conditions = ["p.Price BETWEEN 1 AND 100000"];

                if ($filter_color) {
                    $conditions[] = "p.Prod_color = '$filter_color'";
                }
                if ($filter_brand) {
                    $conditions[] = "p.Brand = '$filter_brand'";
                }
                if ($filter_type) {
                    $conditions[] = "p.Prod_type = '$filter_type'";
                }
                if ($filter_price_min !== '' && $filter_price_max !== '' && $filter_price_min <= $filter_price_max) {
                    $conditions[] = "p.Price BETWEEN $filter_price_min AND $filter_price_max";
                } elseif ($filter_price_min !== '') {
                    $conditions[] = "p.Price >= $filter_price_min";
                } elseif ($filter_price_max !== '') {
                    $conditions[] = "p.Price <= $filter_price_max";
                }

                $where_clause = implode(" AND ", $conditions);
                $all_products_query = "SELECT p.ProdID, p.Prod_name, p.Prod_description, p.Prod_color, p.Price, p.Image, p.Brand, p.Prod_type, s.StoreBrandName, b.BusinessName
                                       FROM product p
                                       JOIN store s ON p.StoreID = s.StoreID
                                       JOIN businessinfo b ON s.BusinessID = b.BusinessID
                                       WHERE $where_clause $business_filter AND p.status = 'active' AND s.status = 'active' AND b.Visibility = 1 AND b.status = 'active'";
                $stmt_all = $conn->prepare($all_products_query);
                $stmt_all->execute();
                $all_products_result = $stmt_all->get_result();

                echo '<div class="search-results"><h2>All Products</h2><div class="product-card">';
                if ($all_products_result->num_rows > 0) {
                    while ($row = $all_products_result->fetch_assoc()) {
                        // Link to product_details_online.php with all parameters
                        $details_url = "product_details_online.php?id=" . $row['ProdID'];
                        if (isset($_GET['business_id'])) {
                            $details_url .= "&business_id=" . urlencode($_GET['business_id']);
                        }
                        if (isset($_GET['filter_color'])) {
                            $details_url .= "&filter_color=" . urlencode($_GET['filter_color']);
                        }
                        if (isset($_GET['filter_brand'])) {
                            $details_url .= "&filter_brand=" . urlencode($_GET['filter_brand']);
                        }
                        if (isset($_GET['filter_type'])) {
                            $details_url .= "&filter_type=" . urlencode($_GET['filter_type']);
                        }
                        if (isset($_GET['filter_price_min'])) {
                            $details_url .= "&filter_price_min=" . urlencode($_GET['filter_price_min']);
                        }
                        if (isset($_GET['filter_price_max'])) {
                            $details_url .= "&filter_price_max=" . urlencode($_GET['filter_price_max']);
                        }

                        echo '<div class="product-item">';
                        echo '<a href="' . $details_url . '"><img src="uploads/' . htmlspecialchars($row['Image']) . '" alt="Product Image"></a>';
                        echo '<h3>' . htmlspecialchars($row['Prod_name']) . '</h3>';
                        echo '<p>Brand: ' . htmlspecialchars($row['Brand']) . '</p>';
                        echo '<p>Type: ' . htmlspecialchars($row['Prod_type']) . '</p>';
                        echo '<p>Price: ₱' . number_format($row['Price'], 0) . '</p>';
                        echo '<p>' . htmlspecialchars($row['Prod_description']) . '</p>';
                        echo '<p>Color: ' . htmlspecialchars($row['Prod_color']) . '</p>';
                        echo '<p>Store: ' . htmlspecialchars($row['StoreBrandName']) . '</p>';
                        echo '<p>Business: ' . htmlspecialchars($row['BusinessName']) . '</p>';
                        echo '</div>';
                    }
                } else {
                    echo '<p>No products available.</p>';
                }
                echo '</div></div>';
                $stmt_all->close();
            }
            ?>
        </div>
    </div>
</body>
</html>