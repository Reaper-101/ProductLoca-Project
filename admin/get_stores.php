<?php
include '../conn/conn.php';

if (isset($_GET['businessID']) && is_numeric($_GET['businessID'])) {
    $businessID = (int)$_GET['businessID'];
    $query = "SELECT StoreID, StoreBrandName, StoreDescription 
              FROM store 
              WHERE BusinessID = ? AND status = 'active'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $businessID);
    $stmt->execute();
    $result = $stmt->get_result();

    $stores = [];
    while ($row = $result->fetch_assoc()) {
        $stores[] = $row;
    }
    echo json_encode($stores);
    $stmt->close();
} else {
    echo json_encode([]);
}
?>