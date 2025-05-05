<?php
session_start();
include '../conn/conn.php'; // Include database connection

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

// Fetch existing stores for dropdown selection, restricted to the business
$sql_stores = "SELECT StoreID, StoreBrandName FROM store WHERE BusinessID = ?";
$stmt_stores = $conn->prepare($sql_stores);
$stmt_stores->bind_param("i", $businessID);
$stmt_stores->execute();
$result_stores = $stmt_stores->get_result();

// Handle form submission for registering a new store manager
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $storeID = (int)$_POST['store'];
    $userType = 'store_manager';
    $status = 'active';

    // Insert new store manager using prepared statement
    $sql_add_manager = "INSERT INTO UserAccount (UserFirstName, UserLastName, Email, Username, Password, UserType, StoreID, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_add_manager = $conn->prepare($sql_add_manager);
    $stmt_add_manager->bind_param("ssssssis", $firstName, $lastName, $email, $username, $password, $userType, $storeID, $status);

    if ($stmt_add_manager->execute()) {
        echo "<script>alert('Store Manager registered successfully!'); window.location.href = 'manage_storemanagers.php';</script>";
    } else {
        echo "<script>alert('Error adding store manager: " . $conn->error . "');</script>";
    }
    $stmt_add_manager->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Store Manager</title>
    <style>
        body {
            background: #ffffff;
        }
        .container { width: 600px; margin: 50px auto; background: #023047; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        .container label {
            color: #ffffff;
        }
        h2 { text-align: center; color: #ff7200; }
        input[type="text"], input[type="email"], input[type="password"], select { width: 100%; padding: 10px; margin: 10px 0; border-radius: 5px; border: 1px solid #ccc; }
        .button-group { display: flex; justify-content: space-between; }
        .button-group button { width: 48%; padding: 10px; background-color: #ff7200; color: #fff; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>

<div class="container">
    <h2>Add New Store Manager</h2>
    <form method="POST" action="add_storemanager.php">
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

        <label for="store">Assign to Store:</label>
        <select id="store" name="store" required>
            <option value="">Select Store</option>
            <?php while ($row = $result_stores->fetch_assoc()) { ?>
                <option value="<?php echo $row['StoreID']; ?>"><?php echo $row['StoreBrandName']; ?></option>
            <?php } ?>
        </select>

        <div class="button-group">
            <button type="button" onclick="window.location.href='manage_storemanagers.php';">Back</button>
            <button type="submit">Register Store Manager</button>
        </div>
    </form>
</div>

</body>
</html>
<?php
$stmt_stores->close();
$conn->close();
?>