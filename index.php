<?php
date_default_timezone_set('Asia/Manila'); // Local time zone
session_start();
include 'conn/conn.php'; // Include the database connection

// Check if kiosk is set up
if (!isset($_SESSION['kioskID']) || !isset($_SESSION['kioskNum']) || !isset($_SESSION['kioskCode']) || !isset($_SESSION['business_id'])) {
    echo "<script>alert('Kiosk not set up. Please configure it first.'); window.location.href = 'admin/setup_kiosk.php';</script>";
    exit();
}

$kioskID = (int)$_SESSION['kioskID'];
$businessID = (int)$_SESSION['business_id'];

// Clear session search data if coming back to the main dashboard
if (!isset($_GET['search_query'])) {
    unset($_SESSION['search_query']);
}

// Handle search query counting
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

// Fetch business name
$business_query = "SELECT BusinessName FROM businessinfo WHERE BusinessID = ? AND status = 'active'";
$stmt_business = $conn->prepare($business_query);
$stmt_business->bind_param("i", $businessID);
$stmt_business->execute();
$business_result = $stmt_business->get_result();
$business_name = $business_result->num_rows > 0 ? $business_result->fetch_assoc()['BusinessName'] : 'Unknown Business';
$stmt_business->close();

// Fetch unique colors, brands, and product types for filters
$color_query = "SELECT DISTINCT Prod_color FROM product WHERE status = 'active' AND StoreID IN (SELECT StoreID FROM store WHERE BusinessID = ? AND status = 'active') ORDER BY Prod_color";
$stmt_colors = $conn->prepare($color_query);
$stmt_colors->bind_param("i", $businessID);
$stmt_colors->execute();
$colors_result = $stmt_colors->get_result();
$colors = $colors_result->fetch_all(MYSQLI_ASSOC);
$stmt_colors->close();

$brand_query = "SELECT DISTINCT Brand FROM product WHERE status = 'active' AND StoreID IN (SELECT StoreID FROM store WHERE BusinessID = ? AND status = 'active') ORDER BY Brand";
$stmt_brands = $conn->prepare($brand_query);
$stmt_brands->bind_param("i", $businessID);
$stmt_brands->execute();
$brands_result = $stmt_brands->get_result();
$brands = $brands_result->fetch_all(MYSQLI_ASSOC);
$stmt_brands->close();

$type_query = "SELECT DISTINCT Prod_type FROM product WHERE status = 'active' AND StoreID IN (SELECT StoreID FROM store WHERE BusinessID = ? AND status = 'active') ORDER BY Prod_type";
$stmt_types = $conn->prepare($type_query);
$stmt_types->bind_param("i", $businessID);
$stmt_types->execute();
$types_result = $stmt_types->get_result();
$types = $types_result->fetch_all(MYSQLI_ASSOC);
$stmt_types->close();

// Get filter values from GET parameters
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
    <title>Product Loca - <?php echo htmlspecialchars($business_name); ?> Kiosk</title>
    <link rel="stylesheet" href="css/index_style.css?v=<?php echo time(); ?>">
    <style>
        /* Inline CSS for PHP-driven back arrow visibility */
        <?php if (isset($_GET['search_query']) && !empty($_GET['search_query'])): ?>
        .back-arrow { display: inline-block; }
        <?php endif; ?>
        /* Style for the nav menu */
        .nav-menu {
            margin-left: auto;
        }
        .nav-menu ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
        }
        .nav-menu li {
            margin-right: 300px;
        }
        .nav-menu a {
            text-decoration: none;
            color: rgb(255, 187, 0); 
            font-weight: bold;
            font-size: 20px;
        }
        .nav-menu a:hover {
            color:rgb(237, 238, 238); /* Hover color */
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
    <div class="header">
        <div class="navbar">
            <div class="icon">
                <img src="image/ourlogo.png" alt="Logo 1" class="logo-img">
            </div>
            <div class="nav-menu">
                <ul>
                    <li><a href="customer_dashboard.php">Customer Dashboard</a></li>
                </ul>
            </div>
            <div class="search">
                <form id="searchForm" action="index.php" method="GET">
                    <div class="search-container">
                        <a href="index.php" class="back-arrow">←</a>
                        <input class="srch" type="search" name="search_query" placeholder="Search Product or Price (e.g., 500 t-shirt)" value="<?php echo isset($_GET['search_query']) ? htmlspecialchars($_GET['search_query']) : ''; ?>">
                    </div>
                    <button type="submit" class="btn">Search</button>
                </form>
            </div>
        </div>
    </div>

    <div class="main">
        <div class="content">
            <!-- Filter Section -->
            <div class="filter-section">
                <h3>Filters</h3>
                <form method="GET" action="index.php">
                    <?php if (isset($_GET['search_query'])): ?>
                        <input type="hidden" name="search_query" value="<?php echo htmlspecialchars($_GET['search_query']); ?>">
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
                        $price_conditions[] = "p.Price <= $price";
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
                $query = "SELECT p.ProdID, p.Prod_name, p.Prod_type, p.Prod_description, p.Price, p.Image, p.Prod_color, p.Brand, s.StoreBrandName
                          FROM product p
                          JOIN store s ON p.StoreID = s.StoreID
                          WHERE s.BusinessID = ? AND $where_clause AND p.status = 'active' AND s.status = 'active'";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $businessID);
                $stmt->execute();
                $result = $stmt->get_result();

                echo '<div class="search-results"><h2>Search Results for "' . htmlspecialchars($search_query) . '" - ' . htmlspecialchars($business_name) . '</h2><div class="product-card">';

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo '<div class="product-item">';
                        echo '<a href="product_details.php?id=' . $row['ProdID'] . '&search_query=' . urlencode($search_query) . '"><img src="uploads/' . htmlspecialchars($row['Image']) . '" alt="Product Image"></a>';
                        echo '<h3>' . htmlspecialchars($row['Prod_name']) . '</h3>';
                        echo '<p>Brand: ' . htmlspecialchars($row['Brand']) . '</p>';
                        echo '<p>Type: ' . htmlspecialchars($row['Prod_type']) . '</p>';
                        echo '<p>Price: ₱' . number_format($row['Price'], 0) . '</p>';
                        echo '<p>' . htmlspecialchars($row['Prod_description']) . '</p>';
                        echo '<p>Color: ' . htmlspecialchars($row['Prod_color']) . '</p>';
                        echo '<p>Store: ' . htmlspecialchars($row['StoreBrandName']) . '</p>';
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
                $all_products_query = "SELECT p.ProdID, p.Prod_name, p.Prod_description, p.Prod_color, p.Price, p.Image, p.Brand, p.Prod_type, s.StoreBrandName
                                       FROM product p
                                       JOIN store s ON p.StoreID = s.StoreID
                                       WHERE s.BusinessID = ? AND $where_clause AND p.status = 'active' AND s.status = 'active'";
                $stmt_all = $conn->prepare($all_products_query);
                $stmt_all->bind_param("i", $businessID);
                $stmt_all->execute();
                $all_products_result = $stmt_all->get_result();

                echo '<div class="search-results"><h2>All Products - ' . htmlspecialchars($business_name) . '</h2><div class="product-card">';
                if ($all_products_result->num_rows > 0) {
                    while ($row = $all_products_result->fetch_assoc()) {
                        echo '<div class="product-item">';
                        echo '<a href="product_details.php?id=' . $row['ProdID'] . '"><img src="uploads/' . htmlspecialchars($row['Image']) . '" alt="Product Image"></a>';
                        echo '<h3>' . htmlspecialchars($row['Prod_name']) . '</h3>';
                        echo '<p>Brand: ' . htmlspecialchars($row['Brand']) . '</p>';
                        echo '<p>Type: ' . htmlspecialchars($row['Prod_type']) . '</p>';
                        echo '<p>Price: ₱' . number_format($row['Price'], 0) . '</p>';
                        echo '<p>' . htmlspecialchars($row['Prod_description']) . '</p>';
                        echo '<p>Color: ' . htmlspecialchars($row['Prod_color']) . '</p>';
                        echo '<p>Store: ' . htmlspecialchars($row['StoreBrandName']) . '</p>';
                        echo '</div>';
                    }
                } else {
                    echo '<p>No products available for this business.</p>';
                }
                echo '</div></div>';
                $stmt_all->close();
            }
            ?>
        </div>
    </div>
</body>
</html>