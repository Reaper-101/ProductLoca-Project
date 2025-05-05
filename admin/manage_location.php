<?php
session_start();
include '../conn/conn.php'; // Include the database connection

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

// Count total, active, and inactive locations
$sql_count_locations = "SELECT 
    COUNT(*) AS total_locations, 
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_locations, 
    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) AS inactive_locations
FROM location WHERE BusinessID = ?";
$stmt_count = $conn->prepare($sql_count_locations);
$stmt_count->bind_param("i", $businessID);
$stmt_count->execute();
$count_result = $stmt_count->get_result();
$counts = $count_result->fetch_assoc() ?? ['total_locations' => 0, 'active_locations' => 0, 'inactive_locations' => 0];
$stmt_count->close();

// Fetch existing locations with album and image counts
$sql_locations = "
    SELECT 
        l.LocID, l.LocName, l.status,
        COUNT(la.AlbumID) AS album_count,
        (SELECT COUNT(*) FROM locationimage li 
         JOIN location_albums la2 ON li.AlbumID = la2.AlbumID 
         WHERE la2.LocID = l.LocID) AS image_count
    FROM location l
    LEFT JOIN location_albums la ON l.LocID = la.LocID
    WHERE l.BusinessID = ?
    GROUP BY l.LocID, l.LocName, l.status";
$stmt_locations = $conn->prepare($sql_locations);
$stmt_locations->bind_param("i", $businessID);
$stmt_locations->execute();
$result_locations = $stmt_locations->get_result();

// Debug counts
echo "<!-- Debug: Total Locations: {$counts['total_locations']}, Active: {$counts['active_locations']}, Inactive: {$counts['inactive_locations']} -->";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Locations</title>
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
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header h2 { color: #023047; margin: 0; font-size: 24px; font-weight: 600; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin: 20px 0; }
        .search-container input[type="text"] { padding: 8px; width: 200px; border-radius: 5px; border: 1px solid #ddd; font-size: 14px; }
        .add-location-btn { padding: 8px 15px; background-color: #ff7200; color: white; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; font-size: 14px; }
        .add-location-btn:hover { background-color: #ff8c00; }
        .table-wrapper { max-height: 400px; overflow-y: auto; margin-top: 10px; border: 1px solid #ddd; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { padding: 10px; text-align: center; border: 1px solid #ddd; }
        th { background-color: #ff7200; color: #fff; position: sticky; top: 0; }
        tr:hover { background-color: #f1f1f1; cursor: pointer; }
        .status-active { color: green; font-weight: bold; }
        .status-inactive { color: red; font-weight: bold; }
        .action-btn { padding: 5px 10px; background-color: #023047; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 0 5px; }
        .action-btn:hover { background-color: #1b263b; }

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
                <i class="fas fa-map-marker-alt"></i>
                <div>
                    <h3>Total Locations</h3>
                    <p><?php echo $counts['total_locations']; ?></p>
                </div>
            </div>
            <div class="stat-box">
                <i class="fas fa-check-circle"></i>
                <div>
                    <h3>Active Locations</h3>
                    <p><?php echo $counts['active_locations']; ?></p>
                </div>
            </div>
            <div class="stat-box">
                <i class="fas fa-times-circle"></i>
                <div>
                    <h3>Inactive Locations</h3>
                    <p><?php echo $counts['inactive_locations']; ?></p>
                </div>
            </div>
        </div>

        <div class="header">
            <h2>Existing Locations</h2>
        </div>

        <!-- Toolbar with Search Bar and Add Location Button -->
        <div class="toolbar">
            <div class="search-container">
                <input type="text" id="locationSearch" placeholder="Search locations..." onkeyup="searchLocations()">
            </div>
            <a href="add_location.php" class="add-location-btn">Add Location</a>
        </div>

        <!-- Location Table -->
        <div class="table-wrapper">
            <table id="locationTable">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Location Name</th>
                        <th>Status</th>
                        <th>Albums</th>
                        <th>Images</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $locationNumber = 1;
                    while ($row = $result_locations->fetch_assoc()) {
                        $statusClass = $row['status'] === 'active' ? 'status-active' : 'status-inactive';
                        echo "<tr>";
                        echo "<td>" . $locationNumber++ . "</td>";
                        echo "<td>" . htmlspecialchars($row['LocName']) . "</td>";
                        echo "<td><span class='$statusClass'>" . ucfirst($row['status']) . "</span></td>";
                        echo "<td>" . $row['album_count'] . "</td>";
                        echo "<td>" . $row['image_count'] . "</td>";
                        echo "<td>
                            <button class='action-btn' onclick=\"window.location.href='view_location.php?id=" . $row['LocID'] . "'\">View</button>
                            <button class='action-btn' onclick=\"window.location.href='edit_location.php?id=" . $row['LocID'] . "'\">Edit</button>
                        </td>";
                        echo "</tr>";
                    }
                    $stmt_locations->close();
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

        // Search locations function
        function searchLocations() {
            const input = document.getElementById('locationSearch').value.toLowerCase();
            const table = document.getElementById('locationTable');
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) { // Start from 1 to skip header
                const tdName = tr[i].getElementsByTagName('td')[1];
                const tdStatus = tr[i].getElementsByTagName('td')[2];
                if (tdName && tdStatus) {
                    const name = tdName.textContent || tdName.innerText;
                    const status = tdStatus.textContent || tdStatus.innerText;
                    tr[i].style.display = (name.toLowerCase().includes(input) || status.toLowerCase().includes(input)) ? '' : 'none';
                }
            }
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>