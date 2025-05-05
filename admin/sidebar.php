<?php
// session_start();
include '../conn/conn.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.html");
    exit();
}

$businessID = isset($_SESSION['business_id']) ? (int)$_SESSION['business_id'] : null;
if (!$businessID) {
    echo "<script>alert('No business assigned to this admin.'); window.location.href = '../login.html';</script>";
    exit();
}

$sql_business = "SELECT BusinessName FROM businessinfo WHERE BusinessID = ?";
$stmt_business = $conn->prepare($sql_business);
$stmt_business->bind_param("i", $businessID);
$stmt_business->execute();
$result_business = $stmt_business->get_result();
$business = $result_business->num_rows > 0 ? $result_business->fetch_assoc() : null;
$stmt_business->close();

if (!$business) {
    echo "<script>alert('Business not found.'); window.location.href = '../login.html';</script>";
    exit();
}
?>

<div class="sidebar">
    <div class="profile">
        <i class="fas fa-user-circle"></i>
        <h2><?php echo htmlspecialchars($business['BusinessName']); ?></h2>
    </div>
    <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="manage_account.php"><i class="fas fa-user-cog"></i> Manage Account</a>
    <a href="manage_kiosk.php"><i class="fas fa-desktop"></i> Manage Kiosk Device</a>
    <a href="manage_stores.php"><i class="fas fa-store"></i> Manage Stores</a>
    <a href="manage_location.php"><i class="fas fa-map-marker-alt"></i> Manage Map Location</a>
    <a href="manage_storemanagers.php"><i class="fas fa-user-tie"></i> Manage Store Managers</a>
    <a href="analytics.php"><i class="fas fa-chart-line"></i> Frequently Search</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<style>
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
</style>