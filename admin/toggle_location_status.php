<?php
include '../conn/conn.php';

if (isset($_GET['id']) && isset($_GET['status'])) {
    $locID = $conn->real_escape_string($_GET['id']);
    $new_status = ($_GET['status'] == 'active') ? 'inactive' : 'active';

    $sql_update_status = "UPDATE location SET status = '$new_status' WHERE LocID = '$locID'";
    if ($conn->query($sql_update_status) === TRUE) {
        echo "<script>alert('Location status updated successfully!'); window.location.href = 'manage_location.php';</script>";
    } else {
        echo "<script>alert('Error updating status.'); window.location.href = 'manage_location.php';</script>";
    }
} else {
    echo "<script>alert('Invalid parameters.'); window.location.href = 'manage_location.php';</script>";
}
$conn->close();
?>
