<?php
session_start();
include '../conn/conn.php'; // Include the database connection

// Check if the user is logged in and is a superadmin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'superadmin') {
    header("Location: ../login.html"); // Redirect to login if not a superadmin
    exit();
}

// Count total admins, active admins, inactive admins, and businesses with prepared statements
$sql_counts = "
    SELECT 
        (SELECT COUNT(*) FROM UserAccount WHERE UserType = 'admin') AS total_admins,
        (SELECT COUNT(*) FROM UserAccount WHERE UserType = 'admin' AND status = 'active') AS active_admins,
        (SELECT COUNT(*) FROM UserAccount WHERE UserType = 'admin' AND status = 'inactive') AS inactive_admins,
        (SELECT COUNT(*) FROM businessinfo) AS total_businesses
";
$count_results = $conn->query($sql_counts);
$counts = $count_results->fetch_assoc() ?? ['total_admins' => 0, 'active_admins' => 0, 'inactive_admins' => 0, 'total_businesses' => 0];
$totalAdmins = $counts['total_admins'];
$activeAdmins = $counts['active_admins'];
$inactiveAdmins = $counts['inactive_admins'];
$totalBusinesses = $counts['total_businesses'];

// Fetch all admin accounts and their associated businesses with prepared statement
$sql_admins = "
    SELECT ua.UserAccountID, ua.UserFirstName, ua.UserLastName, ua.status, bi.BusinessName 
    FROM UserAccount ua 
    LEFT JOIN businessinfo bi ON ua.BusinessID = bi.BusinessID 
    WHERE ua.UserType = 'admin'";
$stmt_admins = $conn->prepare($sql_admins);
$stmt_admins->execute();
$result_admins = $stmt_admins->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admin Accounts</title>
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
        .sidebar .profile h2 { font-size: 20px; color: #fff; font-weight: 500; margin:0; }
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
        h2 { color: #023047; font-size: 24px; font-weight: 600; margin: 20px 0; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin: 20px 0; }
        .search-container input[type="text"] { padding: 8px; width: 200px; border-radius: 5px; border: 1px solid #ddd; font-size: 14px;margin-bottom:10px; }
        .add-admin-btn { padding: 8px 15px; background-color: #ff7200; color: white; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; font-size: 14px; }
        .add-admin-btn:hover { background-color: #ff8c00; }
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

    <!-- Inline Superadmin Sidebar -->
    <div class="sidebar">
        <div class="profile">
            <i class="fas fa-user-shield"></i>
            <h2><?php echo htmlspecialchars($_SESSION['username']); ?></h2>
        </div>
        <a href="superadmin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="manage_business.php"><i class="fas fa-building"></i> Manage Business</a>
        <!-- <a href="manage_business_visibility.php"><i class="fas fa-eye"></i> Manage Business Visibility</a> -->
        <a href="manage_admins.php"><i class="fas fa-users-cog"></i> Manage Accounts</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="content">
        <!-- Display Statistics -->
        <div class="stats-container">
            <div class="stat-box">
                <i class="fas fa-users-cog"></i>
                <div>
                    <h3>Total Admins</h3>
                    <p><?php echo $totalAdmins; ?></p>
                </div>
            </div>
            <div class="stat-box">
                <i class="fas fa-building"></i>
                <div>
                    <h3>Total Businesses</h3>
                    <p><?php echo $totalBusinesses; ?></p>
                </div>
            </div>
            <div class="stat-box">
                <i class="fas fa-check-circle"></i>
                <div>
                    <h3>Active Admins</h3>
                    <p><?php echo $activeAdmins; ?></p>
                </div>
            </div>
            <div class="stat-box">
                <i class="fas fa-times-circle"></i>
                <div>
                    <h3>Inactive Admins</h3>
                    <p><?php echo $inactiveAdmins; ?></p>
                </div>
            </div>
        </div>

        <!-- Admin Accounts Section -->
        <div class="toolbar">
            <h2>Manage Admin Accounts</h2>
        </div>
        <div class="toolbar">
                <div class="search-container">
                    <input type="text" id="adminSearch" placeholder="Search admins..." onkeyup="searchAdmins()">
                </div>
                <a href="add_admin.php" class="add-admin-btn">Add Admin</a>
        </div>

        <!-- Admin Accounts Table -->
        <div class="table-wrapper">
            <table id="adminTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Business</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $adminNumber = 1;
                    while ($row = $result_admins->fetch_assoc()) {
                        $statusClass = $row['status'] === 'active' ? 'status-active' : 'status-inactive';
                        echo "<tr>";
                        echo "<td>" . $adminNumber++ . "</td>";
                        echo "<td>" . htmlspecialchars($row['UserFirstName'] . " " . $row['UserLastName']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['BusinessName'] ?: 'Unassigned') . "</td>";
                        echo "<td><span class='$statusClass'>" . ucfirst($row['status']) . "</span></td>";
                        echo "<td>
                            <button onclick=\"window.location.href='edit_admin.php?id=" . $row['UserAccountID'] . "'\">Edit</button>
                            <button onclick=\"window.location.href='view_admin.php?id=" . $row['UserAccountID'] . "'\">View</button>
                        </td>";
                        echo "</tr>";
                    }
                    $stmt_admins->close();
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

        // Search admins function
        function searchAdmins() {
            const input = document.getElementById("adminSearch").value.toLowerCase().trim();
            const table = document.getElementById("adminTable");
            const tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) { // Skip header
                const tdName = tr[i].getElementsByTagName("td")[1];
                const tdBusiness = tr[i].getElementsByTagName("td")[2];
                const tdStatus = tr[i].getElementsByTagName("td")[3];
                if (tdName && tdBusiness && tdStatus) {
                    const name = tdName.textContent || tdName.innerText;
                    const business = tdBusiness.textContent || tdBusiness.innerText;
                    const status = tdStatus.textContent || tdStatus.innerText;
                    tr[i].style.display = (name.toLowerCase().includes(input) || 
                                          business.toLowerCase().includes(input) || 
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