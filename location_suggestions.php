<?php
include 'conn/conn.php'; // Include the database connection

// Turn on error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if search_query is set
if (isset($_POST['search_query'])) {
    $search_query = $conn->real_escape_string($_POST['search_query']);
    $search_query = strtolower($search_query);

    // Fetch matching locations based on keywords
    $query = "SELECT DISTINCT CONCAT(FloorLevel, ', ', LocName) AS LocationDetails
              FROM location
              WHERE LOWER(FloorLevel) LIKE '%$search_query%' 
                 OR LOWER(LocName) LIKE '%$search_query%'
              LIMIT 5";

    $result = $conn->query($query);

    // Check for errors in the query
    if (!$result) {
        echo '<div class="suggestion-item">Error: ' . $conn->error . '</div>';
    } else {
        // Output suggestions
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo '<div class="suggestion-item">' . htmlspecialchars($row['LocationDetails']) . '</div>';
            }
        } else {
            echo '<div class="suggestion-item">No suggestions found</div>';
        }
    }
} else {
    echo '<div class="suggestion-item">Invalid request</div>';
}
?>
