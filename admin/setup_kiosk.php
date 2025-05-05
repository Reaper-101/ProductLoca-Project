<?php
session_start();
include '../conn/conn.php'; // Database connection

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin' || !isset($_SESSION['business_id'])) {
    echo "<script>alert('Redirecting to login: Session check failed'); window.location.href = '../login.html';</script>";
    exit();
}

$adminBusinessID = (int)$_SESSION['business_id']; // Get admin's BusinessID from session

// Fetch business details for the admin
$business_query = "SELECT BusinessID, BusinessName FROM businessinfo WHERE BusinessID = ? AND status = 'active'";
$stmt_business = $conn->prepare($business_query);
$stmt_business->bind_param("i", $adminBusinessID);
$stmt_business->execute();
$business_result = $stmt_business->get_result();
if ($business_result->num_rows === 0) {
    echo "<script>alert('No active business assigned to this admin.'); window.location.href = 'admin_dashboard.php';</script>";
    exit();
}
$business = $business_result->fetch_assoc();
$stmt_business->close();

// Handle form submission for kiosk setup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_kiosk'])) {
    $kioskNum = $conn->real_escape_string($_POST['kioskNum']);
    $kioskCode = $conn->real_escape_string($_POST['kioskCode']);

    // Verify kiosk details
    $verify_query = "SELECT KioskID, KioskStatus FROM kioskdevice WHERE KioskNum = ? AND kioskCode = ? AND BusinessID = ? AND status = 'active'";
    $stmt_verify = $conn->prepare($verify_query);
    $stmt_verify->bind_param("ssi", $kioskNum, $kioskCode, $adminBusinessID);
    $stmt_verify->execute();
    $verify_result = $stmt_verify->get_result();

    if ($verify_result->num_rows > 0) {
        $kiosk = $verify_result->fetch_assoc();
        $kioskID = $kiosk['KioskID'];

        // Check if the kiosk is already assigned
        if ($kiosk['KioskStatus'] === 'assigned') {
            echo "<script>alert('This kiosk is already assigned. Contact support to reset it.');</script>";
        } else {
            // Update the kiosk status to 'assigned'
            $update_query = "UPDATE kioskdevice SET KioskStatus = 'assigned' WHERE KioskID = ?";
            $stmt_update = $conn->prepare($update_query);
            $stmt_update->bind_param("i", $kioskID);
            if ($stmt_update->execute()) {
                // Store kiosk details in session for index.php
                $_SESSION['kioskID'] = $kioskID;
                $_SESSION['kioskNum'] = $kioskNum;
                $_SESSION['kioskCode'] = $kioskCode;

                // Redirect to index.php
                echo "<script>alert('Kiosk setup successful! Redirecting to product search...'); window.location.href = '../index.php';</script>";
            } else {
                echo "<script>alert('Failed to set up kiosk. Please try again.');</script>";
            }
            $stmt_update->close();
        }
    } else {
        echo "<script>alert('Invalid Kiosk Number or Code for this business.');</script>";
    }
    $stmt_verify->close();
}

// Fetch kiosks for this business to populate the dropdown
$kiosk_query = "SELECT KioskNum, KioskStatus FROM kioskdevice WHERE BusinessID = ? AND status = 'active'";
$stmt_kiosk = $conn->prepare($kiosk_query);
$stmt_kiosk->bind_param("i", $adminBusinessID);
$stmt_kiosk->execute();
$kiosk_result = $stmt_kiosk->get_result();
$kiosks = $kiosk_result->fetch_all(MYSQLI_ASSOC);
$stmt_kiosk->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Kiosk Device</title>
    <style>
        body { 
            font-family: 'Poppins', sans-serif; 
            background: linear-gradient(135deg, #1C2526 0%, #1B263B 100%);
            background-size: 200% 200%;
            animation: gradientShift 15s ease infinite;
            margin: 0; 
            padding: 20px; 
            color: #fff;
        }
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .container { 
            max-width: 600px; 
            margin: 0 auto; 
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            padding: 20px; 
            border-radius: 10px; 
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 140, 0, 0.2);
        }
        h2 { 
            text-align: center; 
            color: #ff7200; 
        }
        .info { 
            color: #fff; 
            margin-bottom: 15px; 
        }
        label { 
            color: #fff; 
            display: block; 
            margin: 10px 0 5px; 
        }
        select, input[type="text"] { 
            width: 100%; 
            padding: 10px; 
            margin-bottom: 15px; 
            border-radius: 5px; 
            border: 1px solid #1B263B; 
            background: #f9f9f9; 
            color: #333; 
        }
        .button-group { 
            display: flex; 
            justify-content: space-between; 
            margin-top: 20px; 
        }
        .button-group button { 
            width: 48%; 
            padding: 10px; 
            background: linear-gradient(90deg, #1B263B 0%, #ff7200 100%); 
            color: #fff; 
            border: none; 
            border-radius: 25px; 
            cursor: pointer; 
            transition: transform 0.1s ease;
        }
        .button-group button:hover { 
            transform: scale(1.02); 
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Setup Kiosk Device</h2>

    <div class="info">
        <p><strong>Business:</strong> <?php echo htmlspecialchars($business['BusinessName']); ?></p>
    </div>

    <!-- Kiosk Setup Form -->
    <form method="POST" action="setup_kiosk.php">
        <input type="hidden" name="setup_kiosk" value="1">
        <label for="kioskNum">Kiosk Number</label>
        <select id="kioskNum" name="kioskNum" required>
            <option value="">-- Select Kiosk Number --</option>
            <?php
            foreach ($kiosks as $kiosk):
                if ($kiosk['KioskStatus'] !== 'assigned'): // Only show unassigned kiosks
            ?>
                <option value="<?php echo htmlspecialchars($kiosk['KioskNum']); ?>">
                    <?php echo htmlspecialchars($kiosk['KioskNum']); ?>
                </option>
            <?php endif; endforeach; ?>
        </select>

        <label for="kioskCode">Kiosk Code (Enter to Verify)</label>
        <input type="text" id="kioskCode" name="kioskCode" required placeholder="e.g., K002">

        <div class="button-group">
            <button type="button" onclick="window.location.href='admin_dashboard.php';">Back</button>
            <button type="submit">Verify & Proceed</button>
        </div>
    </form>
</div>
</body>
</html>