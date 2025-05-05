<?php
session_start();
include '../conn/conn.php'; // Include the database connection

// Check if the user is logged in and is a store manager
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'store_manager') {
    header("Location: ../login.html"); // Redirect to login if not a store manager
    exit();
}

// Get the logged-in store manager's user ID
$user_id = (int)$_SESSION['user_id'];

// Fetch the StoreID linked to the logged-in store manager with prepared statement
$sql_store = "SELECT StoreID FROM UserAccount WHERE UserAccountID = ?";
$stmt_store = $conn->prepare($sql_store);
$stmt_store->bind_param("i", $user_id);
$stmt_store->execute();
$result_store = $stmt_store->get_result();

if ($result_store->num_rows > 0) {
    $store = $result_store->fetch_assoc();
    $currentStoreID = $store['StoreID'];

    if (!$currentStoreID) {
        echo "<script>alert('No StoreID found for this store manager. Please contact admin.'); window.location.href = 'store_manager_dashboard.php';</script>";
        exit();
    }

    // Count statistics for products with prepared statement
    $sql_product_stats = "
        SELECT 
            COUNT(*) AS total_products,
            COUNT(DISTINCT Brand) AS total_brands,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_products,
            SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) AS inactive_products
        FROM product
        WHERE StoreID = ?";
    $stmt_stats = $conn->prepare($sql_product_stats);
    $stmt_stats->bind_param("i", $currentStoreID);
    $stmt_stats->execute();
    $product_stats_result = $stmt_stats->get_result();
    $productStats = $product_stats_result->fetch_assoc() ?? ['total_products' => 0, 'total_brands' => 0, 'active_products' => 0, 'inactive_products' => 0];
    $totalProducts = $productStats['total_products'];
    $totalBrands = $productStats['total_brands'];
    $activeProducts = $productStats['active_products'];
    $inactiveProducts = $productStats['inactive_products'];
    $stmt_stats->close();

    // Fetch products with prepared statement
    $sql_products = "SELECT p.*, s.StoreBrandName 
                     FROM product p 
                     JOIN store s ON p.StoreID = s.StoreID 
                     WHERE s.StoreID = ?";
    $stmt_products = $conn->prepare($sql_products);
    $stmt_products->bind_param("i", $currentStoreID);
    $stmt_products->execute();
    $result_products = $stmt_products->get_result();
} else {
    echo "<script>alert('No store associated with this store manager.'); window.location.href = 'store_manager_dashboard.php';</script>";
    exit();
}
$stmt_store->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; }

        /* Hamburger */
        .hamburger {
            font-size: 28px;
            position: fixed;
            top: 20px;
            left: 20px;
            cursor: pointer;
            z-index: 1000;
            color: #023047;
            transition: all 0.3s ease;
        }
        .sidebar.hidden ~ .hamburger { left: 20px; }

        /* Sidebar */
        .sidebar {
            position: fixed;
            height: 100vh;
            width: 280px;
            background: linear-gradient(180deg, #023047 0%, #1b263b 100%);
            display: flex;
            flex-direction: column;
            padding: 20px 15px;
            color: #fff;
            transition: all 0.3s ease;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }
        .sidebar.hidden { transform: translateX(-100%); }
        .sidebar .profile {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .sidebar .profile i { font-size: 50px; color: #ff7200; margin-bottom: 10px; }
        .sidebar .profile h2 { font-size: 20px; color: #fff; font-weight: 500; }
        .sidebar a {
            padding: 15px;
            text-decoration: none;
            font-size: 16px;
            color: #fff;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            border-radius: 8px;
            margin: 5px 0;
        }
        .sidebar a:hover, .sidebar a.active {
            background: rgba(255, 114, 0, 0.2);
            color: #ff7200;
            transform: translateX(5px);
        }
        .sidebar a i { margin-right: 12px; font-size: 18px; }

        /* Content */
        .content {
            margin-left: 280px;
            padding: 40px;
            transition: all 0.3s ease;
            min-height: 100vh;
            background: #fff;
            border-radius: 20px 0 0 20px;
            box-shadow: -5px 0 15px rgba(0, 0, 0, 0.05);
        }
        .sidebar.hidden ~ .content { margin-left: 0; border-radius: 20px; }

        .stats-container { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .stat-box { flex: 1; margin: 0 20px; padding: 20px; background-color: #ff7200; color: white; border-radius: 8px; text-align: center; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); display: flex; justify-content: space-around; align-items: center; }
        .stat-box i { font-size: 2.5em; }
        .stat-box h3 { margin-bottom: 10px; font-size: 1.2em; }
        .stat-box p { font-size: 1.5em; font-weight: bold; }
        .header { display: flex; justify-content: space-between; align-items: center; margin: 20px 0; }
        .header h2 { color: #023047; margin: 0; font-size: 24px; font-weight: 600; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin: 20px 0; }
        .search-container input[type="text"] { padding: 8px; width: 200px; border-radius: 5px; border: 1px solid #ddd; font-size: 14px; margin-bottom:10px; }
        .add-product-btn { padding: 8px 15px; background-color: #ff7200; color: white; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; font-size: 14px; }
        .add-product-btn:hover { background-color: #ff8c00; }
        .table-wrapper { max-height: 400px; overflow-y: auto; margin-top: 10px; border: 1px solid #ddd; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { padding: 10px; text-align: center; border: 1px solid #ddd; }
        th { background-color: #ff7200; color: #fff; position: sticky; top: 0; }
        tr:hover { background-color: #f1f1f1; cursor: pointer; }
        .status-active { background-color: #28a745; color: #fff; padding: 5px 10px; border-radius: 5px; }
        .status-inactive { background-color: #dc3545; color: #fff; padding: 5px 10px; border-radius: 5px; }
        button { padding: 5px 10px; background-color: #023047; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 0 5px; }
        button:hover { background-color: #1b263b; }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .content { margin-left: 250px; padding: 30px; }
            .stats-container { flex-direction: column; }
            .stat-box { margin: 10px 0; }
        }
        @media (max-width: 768px) {
            .content { margin-left: 0; padding: 20px; border-radius: 10px; }
            .hamburger { left: 15px; top: 15px; }
            .toolbar { flex-direction: column; gap: 10px; }
            .search-container input[type="text"] { width: 100%; }
            .table-wrapper { max-height: 300px; }
        }
    </style>
</head>
<body>
    <div class="hamburger">
        <i class="fas fa-bars"></i>
    </div>

    <!-- Inline Store Manager Sidebar -->
    <div class="sidebar">
        <div class="profile">
            <i class="fas fa-user-tie"></i>
            <h2><?php echo htmlspecialchars($_SESSION['username']); ?></h2>
        </div>
        <a href="store_manager_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="manage_account.php"><i class="fas fa-user-cog"></i> Manage Account</a>
        <a href="manage_product.php"><i class="fas fa-box-open"></i> Manage Products</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="content">
        <!-- Display Statistics -->
        <div class="stats-container">
            <div class="stat-box">
                <i class="fas fa-box"></i>
                <div>
                    <h3>Total Products</h3>
                    <p><?php echo $totalProducts; ?></p>
                </div>
            </div>
            <div class="stat-box">
                <i class="fas fa-trademark"></i>
                <div>
                    <h3>Total Brands</h3>
                    <p><?php echo $totalBrands; ?></p>
                </div>
            </div>
            <div class="stat-box">
                <i class="fas fa-check-circle"></i>
                <div>
                    <h3>Active Products</h3>
                    <p><?php echo $activeProducts; ?></p>
                </div>
            </div>
            <div class="stat-box">
                <i class="fas fa-times-circle"></i>
                <div>
                    <h3>Inactive Products</h3>
                    <p><?php echo $inactiveProducts; ?></p>
                </div>
            </div>
        </div>

        <!-- Header and Toolbar -->
        <div class="header">
            <h2>Manage Products</h2>
        </div>
            <div class="toolbar">
                <div class="search-container">
                    <input type="text" id="productSearch" placeholder="Search products..." onkeyup="searchProducts()">
                </div>
                <a href="add_product.php" class="add-product-btn">Add Product</a>
            </div>

        <!-- Products Table -->
        <div class="table-wrapper">
            <table id="productTable">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Product Name</th>
                        <th>Brand</th>
                        <th>Store</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $productNumber = 1;
                    while ($row = $result_products->fetch_assoc()) {
                        $statusClass = $row['status'] === 'active' ? 'status-active' : 'status-inactive';
                        echo "<tr>";
                        echo "<td>" . $productNumber++ . "</td>";
                        echo "<td>" . htmlspecialchars($row['Prod_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['Brand']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['StoreBrandName']) . "</td>";
                        echo "<td><span class='$statusClass'>" . ucfirst($row['status']) . "</span></td>";
                        echo "<td>
                            <button onclick=\"window.location.href='edit_product.php?id=" . $row['ProdID'] . "'\">Edit</button>
                            <button onclick=\"window.location.href='view_product.php?id=" . $row['ProdID'] . "'\">View</button>
                        </td>";
                        echo "</tr>";
                    }
                    $stmt_products->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Toggle sidebar
        const sidebar = document.querySelector('.sidebar');
        const hamburger = document.querySelector('.hamburger');

        hamburger.addEventListener('click', function() {
            sidebar.classList.toggle('hidden');
        });

        // Highlight active sidebar link
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname.split('/').pop();
            console.log('Current Path:', currentPath); // Debug URL
            document.querySelectorAll('.sidebar a').forEach(link => {
                const linkPath = link.getAttribute('href').split('/').pop();
                if (linkPath === currentPath) {
                    link.classList.add('active');
                }
            });
        });

        // Search products function
        function searchProducts() {
            const input = document.getElementById("productSearch").value.toLowerCase().trim();
            const table = document.getElementById("productTable");
            const tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) { // Skip header
                const tdName = tr[i].getElementsByTagName("td")[1];
                const tdBrand = tr[i].getElementsByTagName("td")[2];
                const tdStore = tr[i].getElementsByTagName("td")[3];
                const tdStatus = tr[i].getElementsByTagName("td")[4];
                if (tdName && tdBrand && tdStore && tdStatus) {
                    const name = tdName.textContent || tdName.innerText;
                    const brand = tdBrand.textContent || tdBrand.innerText;
                    const store = tdStore.textContent || tdStore.innerText;
                    const status = tdStatus.textContent || tdStatus.innerText;
                    tr[i].style.display = (name.toLowerCase().includes(input) || 
                                          brand.toLowerCase().includes(input) || 
                                          store.toLowerCase().includes(input) || 
                                          status.toLowerCase().includes(input)) ? '' : 'none';
                }
            }
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>