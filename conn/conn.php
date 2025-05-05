<?php
$servername = "localhost:3307"; // Specify the port number here
$username = "root"; // Default XAMPP username
$password = ""; // Default XAMPP password is empty
$dbname = "productlocaproject_db"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
