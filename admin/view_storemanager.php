<?php
session_start();
include '../conn/conn.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.html");
    exit();
}

// Get the store manager ID
if (!isset($_GET['id'])) {
    echo "<script>alert('Invalid store manager ID'); window.location.href = 'manage_storemanagers.php';</script>";
    exit();
}

$managerID = $_GET['id'];

// Fetch store manager details
$sql_manager = "SELECT ua.UserFirstName, ua.UserLastName, ua.Email, ua.Username, ua.status, s.StoreBrandName 
                FROM UserAccount ua
                LEFT JOIN store s ON ua.StoreID = s.StoreID
                WHERE ua.UserAccountID = '$managerID'";
$result_manager = $conn->query($sql_manager);

if ($result_manager->num_rows == 0) {
    echo "<script>alert('Store manager not found'); window.location.href = 'manage_storemanagers.php';</script>";
    exit();
}

$manager = $result_manager->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Store Manager</title>
    <style>

        body {
            background: #ffff;
        }
        .container { width: 600px; margin: 50px auto;  background: #023047;  padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); } 
        h2 { text-align: center; color: #ff7200; }
        .details { margin-top: 20px; font-size: 18px; }
        .details p { margin: 5px 0; color: #ffff; }
        .button-group { display: flex; justify-content: center; margin-top: 20px; }
        .button-group button { padding: 10px 20px; background-color: #ff7200; color: white; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>

<div class="container">
    <h2>View Store Manager</h2>
    <div class="details">
        <p><strong>First Name:</strong> <?php echo htmlspecialchars($manager['UserFirstName']); ?></p>
        <p><strong>Last Name:</strong> <?php echo htmlspecialchars($manager['UserLastName']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($manager['Email']); ?></p>
        <p><strong>Username:</strong> <?php echo htmlspecialchars($manager['Username']); ?></p>
        <p><strong>Store:</strong> <?php echo htmlspecialchars($manager['StoreBrandName']); ?></p>
        <p><strong>Status:</strong> <?php echo ucfirst($manager['status']); ?></p>
    </div>

    <div class="button-group">
        <button onclick="window.location.href='manage_storemanagers.php';">Back</button>
    </div>
</div>

</body>
</html>
