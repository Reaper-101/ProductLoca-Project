<?php
session_start();
include '../conn/conn.php'; // Include the database connection

// Check if the user is logged in and is a super admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'superadmin') {
    header("Location: ../login.html"); // Redirect to login page if not a super admin
    exit();
}

// Check if the admin ID is provided in the URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $admin_id = intval($_GET['id']); // Get admin ID from the URL
} else {
    echo "<script>alert('Invalid Admin ID.'); window.location.href = 'manage_admins.php';</script>";
    exit();
}

// Fetch the details of the selected admin
$sql_admin = "SELECT * FROM UserAccount WHERE UserAccountID = '$admin_id'";
$result_admin = $conn->query($sql_admin);

if ($result_admin->num_rows > 0) {
    $admin = $result_admin->fetch_assoc();
} else {
    echo "<script>alert('Admin not found.'); window.location.href = 'manage_admins.php';</script>";
    exit();
}

// Fetch business info for the dropdown
$sql_business = "SELECT BusinessID, BusinessName FROM businessinfo";
$result_business = $conn->query($sql_business);

// Handle form submission for updating admin details
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $username = $conn->real_escape_string($_POST['username']);
    $business_id = $conn->real_escape_string($_POST['business_id']); // Get selected business
    $status = $conn->real_escape_string($_POST['status']); // Get selected status

    // Check if password is being updated
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the new password
        $sql_update = "UPDATE UserAccount 
                       SET UserFirstName='$first_name', UserLastName='$last_name', Email='$email', Username='$username', Password='$password', BusinessID='$business_id', status='$status' 
                       WHERE UserAccountID='$admin_id'";
    } else {
        $sql_update = "UPDATE UserAccount 
                       SET UserFirstName='$first_name', UserLastName='$last_name', Email='$email', Username='$username', BusinessID='$business_id', status='$status' 
                       WHERE UserAccountID='$admin_id'";
    }

    if ($conn->query($sql_update) === TRUE) {
        echo "<script>alert('Admin details updated successfully!'); window.location.href = 'manage_admins.php';</script>";
    } else {
        echo "<script>alert('Error updating admin details: " . $conn->error . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Admin Account</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #ffff;
        }
        .container {
            width: 600px;
            margin: 50px auto;
            background: #023047;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            color: #ffff;
        }
        input[type="text"], input[type="email"], input[type="password"], select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        label {
            color: #ffff;
        }
        .button-group {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .button-group button {
            width: 48%;
            padding: 10px;
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
    <h2>Edit Admin Account</h2>
    <form method="POST" action="edit_admin.php?id=<?php echo $admin_id; ?>">
        <label for="first_name">First Name</label>
        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($admin['UserFirstName']); ?>" required>

        <label for="last_name">Last Name</label>
        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($admin['UserLastName']); ?>" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($admin['Email']); ?>" required>

        <label for="username">Username</label>
        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($admin['Username']); ?>" required>

        <label for="password">New Password (Leave blank to keep current)</label>
        <input type="password" id="password" name="password">

        <label for="business_id">Assign Business</label>
        <select id="business_id" name="business_id" required>
            <option value="">Select Business</option>
            <?php while ($business = $result_business->fetch_assoc()) { ?>
                <option value="<?php echo $business['BusinessID']; ?>" <?php if ($business['BusinessID'] == $admin['BusinessID']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($business['BusinessName']); ?>
                </option>
            <?php } ?>
        </select>

        <label for="status">User Status</label>
        <select id="status" name="status">
            <option value="active" <?php if ($admin['status'] == 'active') echo 'selected'; ?>>Active</option>
            <option value="inactive" <?php if ($admin['status'] == 'inactive') echo 'selected'; ?>>Inactive</option>
        </select>

        <!-- Back and Update buttons -->
        <div class="button-group">
            <button type="button" onclick="window.location.href='manage_admins.php';">Back</button>
            <button type="submit">Update Admin</button>
        </div>
    </form>
</div>
</body>
</html>
