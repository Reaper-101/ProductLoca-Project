<?php
session_start();
include '../conn/conn.php'; // Include database connection

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.html");
    exit();
}

// Get the store manager ID from the URL
if (isset($_GET['id'])) {
    $storeManagerID = $conn->real_escape_string($_GET['id']);
} else {
    echo "<script>alert('Invalid Store Manager ID.'); window.location.href = 'manage_storemanagers.php';</script>";
    exit();
}

// Fetch store manager details
$sql_store_manager = "SELECT * FROM UserAccount WHERE UserAccountID = '$storeManagerID'";
$result_store_manager = $conn->query($sql_store_manager);
if ($result_store_manager->num_rows > 0) {
    $store_manager = $result_store_manager->fetch_assoc();
} else {
    echo "<script>alert('Store Manager not found.'); window.location.href = 'manage_storemanagers.php';</script>";
    exit();
}

// Update store manager details
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstName = $conn->real_escape_string($_POST['first_name']);
    $lastName = $conn->real_escape_string($_POST['last_name']);
    $status = $conn->real_escape_string($_POST['status']);

    $sql_update = "UPDATE UserAccount SET UserFirstName = '$firstName', UserLastName = '$lastName', status = '$status' WHERE UserAccountID = '$storeManagerID'";
    if ($conn->query($sql_update) === TRUE) {
        echo "<script>alert('Store Manager updated successfully!'); window.location.href = 'manage_storemanagers.php';</script>";
    } else {
        echo "<script>alert('Error updating store manager.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Store Manager</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }
        .container {
            width: 500px;
            margin: 50px auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            color: #ff7200;
        }
        input[type="text"], select, button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button {
            background-color: #ff7200;
            color: #fff;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #ff8c00;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Edit Store Manager</h2>

    <!-- Edit Store Manager Form -->
    <form method="POST" action="edit_storemanager.php?id=<?php echo $storeManagerID; ?>">
        <label for="first_name">First Name</label>
        <input type="text" id="first_name" name="first_name" value="<?php echo $store_manager['UserFirstName']; ?>" required>

        <label for="last_name">Last Name</label>
        <input type="text" id="last_name" name="last_name" value="<?php echo $store_manager['UserLastName']; ?>" required>

        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="active" <?php if ($store_manager['status'] == 'active') echo 'selected'; ?>>Active</option>
            <option value="inactive" <?php if ($store_manager['status'] == 'inactive') echo 'selected'; ?>>Inactive</option>
        </select>

        <button type="submit">Update</button>
        <button type="button" onclick="window.location.href='manage_storemanagers.php';">Back</button>
    </form>
</div>

</body>
</html>
