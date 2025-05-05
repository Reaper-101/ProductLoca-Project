<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include '../conn/conn.php'; // Include the database connection

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.html");
    exit();
}
// Get the logged-in user's ID
$user_id = (int)$_SESSION['user_id'];

// Fetch the store manager's first name with a prepared statement
$sql_user = "SELECT UserFirstName FROM UserAccount WHERE UserAccountID = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();

if ($result_user->num_rows > 0) {
    $user = $result_user->fetch_assoc();
    $first_name = $user['UserFirstName'];
} else {
    $first_name = "User"; // Fallback if no first name is found
}
$stmt_user->close();
// Get the assigned BusinessID for the logged-in admin
$businessID = isset($_SESSION['business_id']) ? (int)$_SESSION['business_id'] : null;

// Check if the business ID is not set in the session, redirect to login
if (!$businessID) {
    echo "<script>alert('No business assigned to this admin. Please contact the Super Admin.'); window.location.href = '../login.html';</script>";
    exit();
}

// Fetch business details using the BusinessID
$sql_business = "SELECT * FROM businessinfo WHERE BusinessID = ?";
$stmt_business = $conn->prepare($sql_business);
$stmt_business->bind_param("i", $businessID);
$stmt_business->execute();
$result_business = $stmt_business->get_result();

if ($result_business->num_rows > 0) {
    $business = $result_business->fetch_assoc();
} else {
    echo "<script>alert('Business not found.'); window.location.href = '../login.html';</script>";
    exit();
}
$stmt_business->close();

// Fetch kiosk data for the business
$sql_kiosks = "SELECT KioskID, KioskNum, kioskCode FROM kioskdevice WHERE BusinessID = ? AND status = 'active'";
$stmt_kiosks = $conn->prepare($sql_kiosks);
$stmt_kiosks->bind_param("i", $businessID);
$stmt_kiosks->execute();
$result_kiosks = $stmt_kiosks->get_result();

// Debug: Output number of kiosks found
echo "<!-- Debug: Kiosks found: " . $result_kiosks->num_rows . " for BusinessID: $businessID -->";

// Handle kiosk setup via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kiosk_id']) && isset($_POST['kiosk_code'])) {
    $kioskID = (int)$_POST['kiosk_id'];
    $kioskCode = $conn->real_escape_string($_POST['kiosk_code']);
    $kioskNum = $conn->real_escape_string($_POST['kiosk_num']);

    $verify_query = "SELECT KioskID FROM kioskdevice WHERE KioskID = ? AND kioskCode = ? AND BusinessID = ? AND status = 'active'";
    $stmt_verify = $conn->prepare($verify_query);
    $stmt_verify->bind_param("isi", $kioskID, $kioskCode, $businessID);
    $stmt_verify->execute();
    $verify_result = $stmt_verify->get_result();

    if ($verify_result->num_rows > 0) {
        $_SESSION['kioskID'] = $kioskID;
        $_SESSION['kioskNum'] = $kioskNum;
        $_SESSION['kioskCode'] = $kioskCode;
        echo "<script>alert('Kiosk verified successfully! Redirecting to product search...'); window.location.href = '../index.php';</script>";
        exit();
    } else {
        echo "<script>alert('Invalid Kiosk Code for Kiosk $kioskNum.');</script>";
    }
    $stmt_verify->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo htmlspecialchars($business['BusinessName']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; }

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
        .content h1 {
            color: #023047;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .content p { color: #666; font-size: 16px; margin-bottom: 30px; }
        .content p strong { color: #ff7200; }

        /* Kiosk Section */
        .kiosk-section { margin-top: 30px; display: block; } /* Always visible on this page */
        .kiosk-section h2 {
            color: #023047;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 20px;
            position: relative;
        }
        .kiosk-section h2::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 50px;
            height: 3px;
            background: rgb(251, 250, 249);
            border-radius: 2px;
        }
        .kiosk-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
        }
        .kiosk-card {
            background: #023047;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            color: #fff;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 114, 0, 0.1);
        }
        .kiosk-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            background: linear-gradient(135deg, #023047 0%, #ff7200 100%);
        }
        .kiosk-card i {
            font-size: 36px;
            color: #ff7200;
            margin-bottom: 15px;
        }
        .kiosk-card h3 {
            font-size: 18px;
            font-weight: 500;
            color: #fff;
            margin: 10px 0;
        }
        .kiosk-card p {
            font-size: 14px;
            color: #d1e8ff;
            margin: 5px 0;
        }
        .kiosk-card .status { font-weight: 600; color: #8ecae6; }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .sidebar { width: 250px; }
            .content { margin-left: 250px; padding: 30px; }
            .kiosk-container { grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); }
        }
        @media (max-width: 768px) {
            .sidebar { width: 100%; z-index: 1000; transform: translateX(-100%); }
            .sidebar.hidden { transform: translateX(-100%); }
            .sidebar:not(.hidden) { transform: translateX(0); }
            .content { margin-left: 0; padding: 20px; border-radius: 10px; }
            .hamburger { left: 15px; top: 15px; }
            .kiosk-card { width: 100%; }
        }
        @media (max-width: 480px) {
            .content h1 { font-size: 24px; }
            .kiosk-section h2 { font-size: 20px; }
            .kiosk-card i { font-size: 30px; }
            .kiosk-card h3 { font-size: 16px; }
        }
    </style>
</head>
<body>
    <!-- Hamburger Menu -->
    <div class="hamburger">
        <i class="fas fa-bars"></i>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="profile">
            <i class="fas fa-user-circle"></i>
            <h2><?php echo htmlspecialchars($business['BusinessName']); ?></h2>
        </div>
        <a href="admin_dashboard.php" data-section="dashboard"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="manage_account.php" data-section="account"><i class="fas fa-user-cog"></i> Manage Account</a>
        <a href="manage_kiosk.php" data-section="kiosk"><i class="fas fa-desktop"></i> Manage Kiosk Device</a>
        <a href="manage_stores.php" data-section="stores"><i class="fas fa-store"></i> Manage Stores</a>
        <a href="floorplan.php"><i class="fas fa-map"></i> Manage Floorplans</a>
        <a href="manage_location.php" data-section="location"><i class="fas fa-map-marker-alt"></i> Manage Map Location</a>
        <a href="manage_storemanagers.php" data-section="managers"><i class="fas fa-user-tie"></i> Manage Store Managers</a>
        <a href="analytics.php" data-section="analytics"><i class="fas fa-chart-line"></i> Frequently Search</a>
        <a href="logout.php" data-section="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Main content area -->
    <div class="content">
    <h1>Welcome, <strong><?php echo htmlspecialchars($first_name); ?></strong>, to your admin dashboard.</h1>
        <p>You are managing the business <strong><?php echo htmlspecialchars($business['BusinessName']); ?></strong></p>

        <!-- Kiosk Section -->
        <div class="kiosk-section" id="kioskSection">
            <h2>Registered Kiosks</h2>
            <div class="kiosk-container">
                <?php if ($result_kiosks->num_rows > 0): ?>
                    <?php while ($kiosk = $result_kiosks->fetch_assoc()): ?>
                        <div class="kiosk-card" data-kiosk-id="<?php echo $kiosk['KioskID']; ?>" data-kiosk-num="<?php echo htmlspecialchars($kiosk['KioskNum']); ?>">
                            <i class="fas fa-desktop"></i>
                            <h3><?php echo htmlspecialchars($kiosk['KioskNum']); ?></h3>
                            <p class="status">Status: Active</p>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No registered kiosks found for this business.</p>
                <?php endif; ?>
                <?php $stmt_kiosks->close(); ?>
            </div>
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

        // Handle kiosk card click
        document.querySelectorAll('.kiosk-card').forEach(card => {
            card.addEventListener('click', function() {
                const kioskID = this.getAttribute('data-kiosk-id');
                const kioskNum = this.getAttribute('data-kiosk-num');
                const kioskCode = prompt(`Enter Kiosk Code for ${kioskNum}:`);
                if (kioskCode) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'admin_dashboard.php';
                    form.style.display = 'none';
                    form.innerHTML = `
                        <input type="hidden" name="kiosk_id" value="${kioskID}">
                        <input type="hidden" name="kiosk_code" value="${kioskCode}">
                        <input type="hidden" name="kiosk_num" value="${kioskNum}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    </script>
</body>
</html>
<?php
// Close the database connection
$conn->close();
?>