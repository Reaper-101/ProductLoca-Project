<?php
session_start();
include '../conn/conn.php'; // Include database connection

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.html");
    exit();
}

// Get the assigned BusinessID for the logged-in admin
$businessID = isset($_SESSION['business_id']) ? (int)$_SESSION['business_id'] : null;

if (!$businessID) {
    echo "<script>alert('No business assigned to this admin. Please contact the Super Admin.'); window.location.href = '../login.html';</script>";
    exit();
}

// Count total store managers linked to the business
$sql_count_store_managers = "
    SELECT COUNT(DISTINCT ua.UserAccountID) AS total_managers
    FROM UserAccount ua
    LEFT JOIN store s ON ua.StoreID = s.StoreID
    WHERE ua.UserType = 'store_manager' AND s.BusinessID = ?";
$stmt_count_managers = $conn->prepare($sql_count_store_managers);
$stmt_count_managers->bind_param("i", $businessID);
$stmt_count_managers->execute();
$count_store_managers_result = $stmt_count_managers->get_result();
$storeManagersCount = $count_store_managers_result->fetch_assoc()['total_managers'] ?? 0;
$stmt_count_managers->close();

// Count total, active, and inactive stores
$sql_count_stores = "
    SELECT 
        COUNT(*) AS total_stores, 
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_stores, 
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) AS inactive_stores 
    FROM store 
    WHERE BusinessID = ?";
$stmt_count_stores = $conn->prepare($sql_count_stores);
$stmt_count_stores->bind_param("i", $businessID);
$stmt_count_stores->execute();
$count_stores_result = $stmt_count_stores->get_result();
$storeCounts = $count_stores_result->fetch_assoc() ?? ['total_stores' => 0, 'active_stores' => 0, 'inactive_stores' => 0];
$stmt_count_stores->close();

// Fetch existing store managers linked to the business
$sql_store_managers = "
    SELECT ua.UserAccountID, ua.UserFirstName, ua.UserLastName, ua.status, s.StoreBrandName
    FROM UserAccount ua
    LEFT JOIN store s ON ua.StoreID = s.StoreID
    WHERE ua.UserType = 'store_manager' AND s.BusinessID = ?";
$stmt_store_managers = $conn->prepare($sql_store_managers);
$stmt_store_managers->bind_param("i", $businessID);
$stmt_store_managers->execute();
$result_store_managers = $stmt_store_managers->get_result();

// Debug counts
echo "<!-- Debug: Total Managers: $storeManagersCount, Total Stores: {$storeCounts['total_stores']}, Active Stores: {$storeCounts['active_stores']}, Inactive Stores: {$storeCounts['inactive_stores']} -->";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Store Managers</title>
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
        .search-container input[type="text"] { padding: 8px; width: 200px; border-radius: 5px; border: 1px solid #ddd; font-size: 14px; }
        .add-manager-btn { padding: 8px 15px; background-color: #ff7200; color: white; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; font-size: 14px; }
        .add-manager-btn:hover { background-color: #ff8c00; }
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
            .table-wrapper { max-height: 300px; }
        }
    </style>
</head>
<body>
    <div class="hamburger">
        <i class="fas fa-bars"></i>
    </div>
    <?php include 'sidebar.php'; ?>

    <div class="content">
        <!-- Display Boxes -->
        <div class="stats-container">
            <div class="stat-box">
                <i class="fas fa-user-tie"></i>
                <div>
                    <h3>Total Store Managers</h3>
                    <p><?php echo $storeManagersCount; ?></p>
                </div>
            </div>
            <div class="stat-box">
                <i class="fas fa-store"></i>
                <div>
                    <h3>Total Stores</h3>
                    <p><?php echo $storeCounts['total_stores']; ?></p>
                </div>
            </div>
            <div class="stat-box">
                <i class="fas fa-check-circle"></i>
                <div>
                    <h3>Active Stores</h3>
                    <p><?php echo $storeCounts['active_stores']; ?></p>
                </div>
            </div>
            <div class="stat-box">
                <i class="fas fa-times-circle"></i>
                <div>
                    <h3>Inactive Stores</h3>
                    <p><?php echo $storeCounts['inactive_stores']; ?></p>
                </div>
            </div>
        </div>

        <!-- Existing Store Managers Section -->
        <div class="header">
            <h2>Existing Store Managers</h2>
       </div>
       <div class="toolbar">
                <div class="search-container">
                    <input type="text" id="managerSearch" placeholder="Search managers..." onkeyup="searchManagers()">
                </div>
                <a href="add_storemanager.php" class="add-manager-btn">Add Store Manager</a>
        </div>

        <!-- Display Existing Store Managers -->
        <div class="table-wrapper">
            <table id="managerTable">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Name</th>
                        <th>Store</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $managerNumber = 1;
                    while ($row = $result_store_managers->fetch_assoc()) {
                        $statusClass = $row['status'] === 'active' ? 'status-active' : 'status-inactive';
                        echo "<tr>";
                        echo "<td>" . $managerNumber++ . "</td>";
                        echo "<td>" . htmlspecialchars($row['UserFirstName'] . ' ' . $row['UserLastName']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['StoreBrandName'] ?: 'Not Assigned') . "</td>";
                        echo "<td><span class='$statusClass'>" . ucfirst($row['status']) . "</span></td>";
                        echo "<td>
                            <button onclick=\"window.location.href='edit_storemanager.php?id=" . $row['UserAccountID'] . "'\">Edit</button>
                            <button onclick=\"window.location.href='view_storemanager.php?id=" . $row['UserAccountID'] . "'\">View</button>
                        </td>";
                        echo "</tr>";
                    }
                    $stmt_store_managers->close();
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

        // Search managers function
        function searchManagers() {
            const input = document.getElementById('managerSearch').value.toLowerCase().trim();
            const table = document.getElementById('managerTable');
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) { // Skip header
                const tdName = tr[i].getElementsByTagName('td')[1];
                const tdStore = tr[i].getElementsByTagName('td')[2];
                const tdStatus = tr[i].getElementsByTagName('td')[3];
                if (tdName && tdStore && tdStatus) {
                    const name = tdName.textContent || tdName.innerText;
                    const store = tdStore.textContent || tdStore.innerText;
                    const status = tdStatus.textContent || tdStatus.innerText;
                    tr[i].style.display = (name.toLowerCase().includes(input) || 
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