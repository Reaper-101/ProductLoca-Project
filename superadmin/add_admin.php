<?php
session_start();
include '../conn/conn.php'; // Include the database connection

// Check if the user is logged in and is a superadmin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'superadmin') {
    header("Location: ../login.html");
    exit();
}

// Fetch business info for the dropdown
$sql_business = "SELECT BusinessID, BusinessName FROM businessinfo";
$result_business = $conn->query($sql_business);

// Handle form submission for adding a new admin
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstName = $conn->real_escape_string($_POST['first_name']);
    $lastName = $conn->real_escape_string($_POST['last_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $username = $conn->real_escape_string($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $businessID = $conn->real_escape_string($_POST['business_id']);

    $sql_add_admin = "INSERT INTO UserAccount (UserFirstName, UserLastName, Email, Username, Password, UserType, BusinessID, status) 
                      VALUES ('$firstName', '$lastName', '$email', '$username', '$password', 'admin', '$businessID', 'active')";
    if ($conn->query($sql_add_admin) === TRUE) {
        echo "<script>alert('Admin account added successfully!'); window.location.href = 'manage_admins.php';</script>";
    } else {
        echo "<script>alert('Error adding admin account: " . $conn->error . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Admin</title>
    <style>

         body {
            background: #ffff;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background:  #023047;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .container label{
            color: #ffff;
        }
        h2 {
            color: #ff8c00;
            text-align: center;
        }
        input[type="text"], input[type="email"], input[type="password"], select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        .button-group {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .button-group button {
            padding: 10px 20px;
            background: #ff8c00;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 48%;
        }
        .button-group button:hover {
            background-color: #ff8c00;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Add New Admin</h2>
    <form method="POST" action="add_admin.php">
        <label for="first_name">First Name:</label>
        <input type="text" id="first_name" name="first_name" required>

        <label for="last_name">Last Name:</label>
        <input type="text" id="last_name" name="last_name" required>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>

        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>

        <label for="business_id">Assign Business:</label>
        <select id="business_id" name="business_id" required>
            <option value="">Select Business</option>
            <?php while ($business = $result_business->fetch_assoc()) { ?>
                <option value="<?php echo $business['BusinessID']; ?>"><?php echo $business['BusinessName']; ?></option>
            <?php } ?>
        </select>

        <div class="button-group">
            <button type="button" onclick="window.location.href='manage_admins.php';">Back</button>
            <button type="submit">Add Admin</button>
        </div>
    </form>
</div>

</body>
</html>
