<?php
session_start();
include '../conn/conn.php'; // Ensure this is the correct path to your connection script

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.html");
    exit();
}

$businessID = isset($_SESSION['business_id']) ? (int)$_SESSION['business_id'] : null;

if (!$businessID) {
    echo "<script>alert('No business assigned to this admin. Please contact the Super Admin.'); window.location.href = '../login.html';</script>";
    exit();
}

// Fetch kiosk counts (using KioskStatus as per your original query)
$sql_count_kiosks = "SELECT 
    COUNT(*) AS total_kiosks, 
    SUM(CASE WHEN KioskStatus = 'active' THEN 1 ELSE 0 END) AS active_kiosks,
    SUM(CASE WHEN KioskStatus = 'inactive' THEN 1 ELSE 0 END) AS inactive_kiosks
FROM kioskdevice 
WHERE BusinessID = ?";
$stmt_count = $conn->prepare($sql_count_kiosks);
$stmt_count->bind_param("i", $businessID);
$stmt_count->execute();
$count_result = $stmt_count->get_result();
$counts = $count_result->fetch_assoc() ?? ['total_kiosks' => 0, 'active_kiosks' => 0, 'inactive_kiosks' => 0];
$stmt_count->close();

// Fetch kiosk data
$sql_kiosks = "SELECT * FROM kioskdevice WHERE BusinessID = ?";
$stmt_kiosks = $conn->prepare($sql_kiosks);
$stmt_kiosks->bind_param("i", $businessID);
$stmt_kiosks->execute();
$result_kiosks = $stmt_kiosks->get_result();

// Debug kiosk count
echo "<!-- Debug: Total Kiosks: {$counts['total_kiosks']}, Active: {$counts['active_kiosks']}, Inactive: {$counts['inactive_kiosks']} -->";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Kiosk Devices</title>
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
        .header { display: flex; justify-content: space-between; align-items: center; }
        .header h2 { color: #023047; margin: 0; font-size: 24px; font-weight: 600; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin: 20px 0; }
        .search-container input[type="text"] { padding: 8px; width: 200px; border-radius: 5px; border: 1px solid #ddd; font-size: 14px; }
        .table-wrapper { max-height: 400px; overflow-y: auto; margin-top: 10px; border: 1px solid #ddd; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { padding: 10px; text-align: center; border: 1px solid #ddd; }
        th { background-color: #ff7200; color: #fff; position: sticky; top: 0; }
        tr:hover { background-color: #f1f1f1; cursor: pointer; }
        .status-active { color: green; font-weight: bold; }
        .status-inactive { color: red; font-weight: bold; }
        .add-kiosk-btn { padding: 8px 15px; background-color: #ff7200; color: white; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; font-size: 14px; }
        .add-kiosk-btn:hover { background-color: #ff8c00; }
        button { padding: 5px 10px; margin: 0 5px; background-color: #023047; color: white; border: none; border-radius: 5px; cursor: pointer; }
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

    <!-- Main Content -->
    <div class="content">
        <div class="stats-container">
            <div class="stat-box">
                <i class="fas fa-tv"></i>
                <h3>Total Kiosks</h3>
                <p><?php echo $counts['total_kiosks']; ?></p>
            </div>
            <div class="stat-box">
                <i class="fas fa-check-circle"></i>
                <h3>Active Kiosks</h3>
                <p><?php echo $counts['active_kiosks']; ?></p>
            </div>
            <div class="stat-box">
                <i class="fas fa-times-circle"></i>
                <h3>Inactive Kiosks</h3>
                <p><?php echo $counts['inactive_kiosks']; ?></p>
            </div>
        </div>
        <div class="header">
            <h2>Existing Kiosk Devices</h2>
        </div>
        <div class="toolbar">
            <div class="search-container">
                <input type="text" id="kioskSearch" placeholder="Search kiosks..." onkeyup="searchKiosks()">
            </div>
            <div>
                <a href="add_kiosk.php" class="add-kiosk-btn">Add Kiosk</a>
            </div>
        </div>
        <div class="table-wrapper">
            <table id="kioskTable">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Location</th>
                        <th>Number</th>
                        <th>Kiosk Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $kioskNumber = 1;
                    while ($row = $result_kiosks->fetch_assoc()) {
                        $statusClass = 'status-' . strtolower($row['KioskStatus']);
                        echo "<tr>";
                        echo "<td>" . $kioskNumber++ . "</td>";
                        echo "<td>" . htmlspecialchars($row['KioskLoc']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['KioskNum']) . "</td>";
                        echo "<td><span class='$statusClass'>" . ucfirst($row['KioskStatus']) . "</span></td>";
                        echo "<td>
                            <button onclick=\"window.location.href='edit_kiosk.php?id=" . $row['KioskID'] . "'\">Edit</button>
                            <button onclick=\"window.location.href='view_kiosk.php?id=" . $row['KioskID'] . "'\">View</button>
                        </td>";
                        echo "</tr>";
                    }
                    $stmt_kiosks->close();
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

        // Search kiosks function
        function searchKiosks() {
            const input = document.getElementById('kioskSearch').value.toLowerCase();
            const table = document.getElementById('kioskTable');
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) { // Start from 1 to skip header
                const tdLocation = tr[i].getElementsByTagName('td')[1];
                const tdNumber = tr[i].getElementsByTagName('td')[2];
                const tdStatus = tr[i].getElementsByTagName('td')[3];
                if (tdLocation && tdNumber && tdStatus) {
                    const location = tdLocation.textContent || tdLocation.innerText;
                    const number = tdNumber.textContent || tdNumber.innerText;
                    const status = tdStatus.textContent || tdStatus.innerText;
                    tr[i].style.display = (location.toLowerCase().includes(input) || 
                                          number.toLowerCase().includes(input) || 
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