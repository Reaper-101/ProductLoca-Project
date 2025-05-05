<?php
include 'conn/conn.php';
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "Connected successfully to " . $conn->host_info . "!<br>";
    $result = $conn->query("SELECT DATABASE()");
    echo "Current database: " . $result->fetch_row()[0];
}
$conn->close();
?>