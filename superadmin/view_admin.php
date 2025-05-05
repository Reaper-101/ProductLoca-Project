<?php
session_start();
include '../conn/conn.php'; // Include the database connection

// Check if an admin ID is provided
if (!isset($_GET['id'])) {
    echo "<script>alert('Admin ID is missing.'); window.location.href = 'manage_admins.php';</script>";
    exit();
}

$adminID = $_GET['id'];

// Fetch admin details
$sql_admin = "SELECT ua.*, bi.BusinessName 
              FROM UserAccount ua 
              LEFT JOIN businessinfo bi ON ua.BusinessID = bi.BusinessID 
              WHERE ua.UserAccountID = '$adminID'";
$result_admin = $conn->query($sql_admin);

if ($result_admin->num_rows > 0) {
    $admin = $result_admin->fetch_assoc();
} else {
    echo "<script>alert('Admin not found.'); window.location.href = 'manage_admins.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Admin</title>
    <style>

        body {
            background: #ffff;
        }
        .container {
            max-width: 600px;
            font-size: 30px;
            margin: 100px auto;
            padding: 50px;
            background: #023047;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #ffff;
            text-align: center;
        }
        .admin-details p {
            font-size: 30px;
            margin: 10px 0;
        }
        label {
            color: #ffff;
        }
        p {
            color: #ffff;
        }
        .button-group {
            text-align: center;
            margin-top: 20px;
        }
        .button-group button {
            padding: 10px 20px;
            background-color: #ff7200;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .button-group button:hover {
            background-color: #ff8c00;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Admin Details</h2>
    <div class="admin-details">
        <p><strong>Name:</strong> <?php echo htmlspecialchars($admin['UserFirstName'] . " " . $admin['UserLastName']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($admin['Email']); ?></p>
        <p><strong>Username:</strong> <?php echo htmlspecialchars($admin['Username']); ?></p>
        <p><strong>Assigned Business:</strong> <?php echo htmlspecialchars($admin['BusinessName'] ?: 'Unassigned'); ?></p>
        <p><strong>Status:</strong> <?php echo ucfirst($admin['status']); ?></p>
    </div>
    <div class="button-group">
        <button onclick="window.location.href='manage_admins.php'">Back</button>
    </div>
</div>

</body>
</html>
