<?php
session_start();
include 'conn/conn.php'; // Include the database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve and sanitize form inputs
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $contact_number = $conn->real_escape_string($_POST['contact_number']);
    $email = $conn->real_escape_string($_POST['email']);
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password']; // Raw password (not hashed yet)
    $user_type = $conn->real_escape_string($_POST['user_type']);
    $business_id = isset($_POST['business_id']) ? $conn->real_escape_string($_POST['business_id']) : NULL;

    // Check if username or email already exists in UserAccount
    $check_query = "SELECT * FROM UserAccount WHERE Username = '$username' OR Email = '$email'";
    $result = $conn->query($check_query);

    if ($result->num_rows > 0) {
        // Username or email already exists
        echo "<script>alert('Username or Email already exists!'); window.location.href = 'register.html';</script>";
        exit();
    }

    // Check if user is registering as admin and validate against the admin table
    if ($user_type === 'admin') {
        $admin_check_query = "SELECT * FROM admin_table WHERE Username = '$username' AND Password = '$password'";
        $admin_check_result = $conn->query($admin_check_query);

        if ($admin_check_result->num_rows === 0) {
            // Admin credentials not found in Admin table
            echo "<script>alert('Admin credentials not found!'); window.location.href = 'register.html';</script>";
            exit();
        }
    }

    // Check if user is registering as store manager and validate against the store manager table
    if ($user_type === 'store_manager') {
        $store_manager_check_query = "SELECT * FROM store_manager_table WHERE Username = '$username' AND Password = '$password'";
        $store_manager_check_result = $conn->query($store_manager_check_query);

        if ($store_manager_check_result->num_rows === 0) {
            // Store Manager credentials not found in Store Manager table
            echo "<script>alert('Store Manager credentials not found!'); window.location.href = 'register.html';</script>";
            exit();
        }
    }

    // Hash the password using bcrypt
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // SQL query to insert the new user into UserAccount
    $sql = "INSERT INTO UserAccount (Username, Password, Email, PhoneNumber, UserFirstName, UserLastName, UserType, BusinessID)
            VALUES ('$username', '$hashed_password', '$email', '$contact_number', '$first_name', '$last_name', '$user_type', ".($business_id ? "'$business_id'" : "NULL").")";

    if ($conn->query($sql) === TRUE) {
        // Registration successful
        echo "<script>alert('Registration successful!'); window.location.href = 'login.html';</script>";
    } else {
        // Error in registration
        echo "<script>alert('Error: " . $conn->error . "'); window.location.href = 'register.html';</script>";
    }
}

// Close the database connection
$conn->close();
?>
