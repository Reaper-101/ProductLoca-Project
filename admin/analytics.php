<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include '../conn/conn.php'; // Adjusted path from /admin/ to root

if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'superadmin'])) {
    header("Location: ../login.html");
    exit();
}

function validate($data) {
    $data = trim($data);
    $data = htmlspecialchars($data);
    return $data; // Removed addslashes as it's unnecessary with prepared statements
}

date_default_timezone_set('Asia/Manila');

$start_date = isset($_GET['start_date']) ? validate($_GET['start_date']) : date('Y-m-d', strtotime('-7 days'));
$end_date = isset($_GET['end_date']) ? validate($_GET['end_date']) : date('Y-m-d');
$current_date = date('Y-m-d');

// Fetch statistics
$sql_stats = "
    SELECT 
        SUM(SearchCount) AS total_searches,
        COUNT(DISTINCT SearchQuery) AS unique_queries
    FROM searchanalytics
    WHERE SearchDate BETWEEN ? AND ?";
$stmt_stats = $conn->prepare($sql_stats);
$stmt_stats->bind_param("ss", $start_date, $end_date);
$stmt_stats->execute();
$stats_result = $stmt_stats->get_result();
$stats = $stats_result->fetch_assoc() ?? ['total_searches' => 0, 'unique_queries' => 0];
$totalSearches = $stats['total_searches'];
$uniqueQueries = $stats['unique_queries'];
$stmt_stats->close();

// Fetch search analytics data with previous search date and count
$query = "
    SELECT 
        s.SearchQuery,
        s.SearchDate,
        SUM(s.SearchCount) AS TotalSearches,
        MAX(s.SearchTime) AS LastSearchTime,
        (SELECT SearchDate 
         FROM searchanalytics s2 
         WHERE s2.SearchQuery = s.SearchQuery 
         AND s2.SearchDate < s.SearchDate 
         ORDER BY s2.SearchDate DESC 
         LIMIT 1) AS PrevSearchDate,
        (SELECT SearchCount 
         FROM searchanalytics s2 
         WHERE s2.SearchQuery = s.SearchQuery 
         AND s2.SearchDate < s.SearchDate 
         ORDER BY s2.SearchDate DESC 
         LIMIT 1) AS PrevSearchCount
    FROM searchanalytics s
    WHERE s.SearchQuery != ''
    AND s.SearchDate BETWEEN ? AND ?
    GROUP BY s.SearchQuery, s.SearchDate
    ORDER BY s.SearchDate DESC, TotalSearches DESC
    LIMIT 10";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

// Handle CSV export functionality
if (isset($_POST['export_csv'])) {
    $csv_file = fopen("search_analytics.csv", "w");
    fputcsv($csv_file, ['Search Query', 'Search Date', 'Total Searches', 'Last Searched', 'Previous Search Date', 'Previous Search Count']);
    
    $stmt_csv = $conn->prepare($query);
    $stmt_csv->bind_param("ss", $start_date, $end_date);
    $stmt_csv->execute();
    $csv_result = $stmt_csv->get_result();
    if ($csv_result) {
        while ($row = $csv_result->fetch_assoc()) {
            fputcsv($csv_file, [
                $row['SearchQuery'],
                $row['SearchDate'],
                $row['TotalSearches'],
                date('h:i:s A', strtotime($row['LastSearchTime'])),
                $row['PrevSearchDate'] ?? 'N/A',
                $row['PrevSearchCount'] ?? 'N/A'
            ]);
        }
    }
    $stmt_csv->close();
    fclose($csv_file);

    header('Content-Type: application/csv');
    header('Content-Disposition: attachment; filename="search_analytics.csv"');
    readfile("search_analytics.csv");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Loca - Analytics</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; }
        .hamburger { font-size: 28px; position: fixed; top: 20px; left: 20px; cursor: pointer; z-index: 1000; color: #023047; transition: all 0.3s ease; }
        .sidebar.hidden ~ .hamburger { left: 20px; }
        .content { margin-left: 280px; padding: 40px; transition: all 0.3s ease; min-height: 100vh; background: #fff; border-radius: 20px 0 0 20px; box-shadow: -5px 0 15px rgba(0, 0, 0, 0.05); }
        .sidebar.hidden ~ .content { margin-left: 0; border-radius: 20px; }
        .stats-container { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .stat-box { flex: 1; margin: 0 20px; padding: 20px; background-color: #ff7200; color: white; border-radius: 8px; text-align: center; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); display: flex; justify-content: space-around; align-items: center; }
        .stat-box i { font-size: 2.5em; }
        .stat-box h3 { margin-bottom: 10px; font-size: 1.2em; }
        .stat-box p { font-size: 1.5em; font-weight: bold; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header h2 { color: #023047; margin: 0; font-size: 24px; font-weight: 600; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin: 20px 0; }
        .search-container input[type="date"] { padding: 8px; width: 150px; border-radius: 5px; border: 1px solid #ddd; font-size: 14px; margin-right: 10px; }
        .filter-btn, .export-btn { padding: 8px 15px; background-color: #ff7200; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; }
        .filter-btn:hover, .export-btn:hover { background-color: #ff8c00; }
        .table-wrapper { max-height: 400px; overflow-y: auto; margin-top: 10px; border: 1px solid #ddd; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { padding: 10px; text-align: center; border: 1px solid #ddd; }
        th { background-color: #ff7200; color: #fff; position: sticky; top: 0; }
        tr:hover { background-color: #f1f1f1; }
        .highlight-current { background-color: #e6ffe6; }

        @media (max-width: 1024px) {
            .content { margin-left: 250px; padding: 30px; }
            .stats-container { flex-direction: column; }
            .stat-box { margin: 10px 0; }
        }
        @media (max-width: 768px) {
            .content { margin-left: 0; padding: 20px; border-radius: 10px; }
            .hamburger { left: 15px; top: 15px; }
            .toolbar { flex-direction: column; gap: 10px; }
            .search-container input[type="date"] { width: 100%; }
            .table-wrapper { max-height: 300px; }
        }
    </style>
</head>
<body>
    <div class="hamburger">
        <i class="fas fa-bars"></i>
    </div>
    <?php include 'sidebar.php'; ?>

    <div class="content">
        <div class="stats-container">
            <div class="stat-box">
                <i class="fas fa-search"></i>
                <div>
                    <h3>Total Searches</h3>
                    <p><?php echo $totalSearches; ?></p>
                </div>
            </div>
            <div class="stat-box">
                <i class="fas fa-list"></i>
                <div>
                    <h3>Unique Queries</h3>
                    <p><?php echo $uniqueQueries; ?></p>
                </div>
            </div>
        </div>

        <div class="header">
            <h2>Search Analytics</h2>
        </div>

        <div class="toolbar">
            <div class="search-container">
                <form action="analytics.php" method="GET">
                    <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
                    <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
                    <button type="submit" class="filter-btn">Filter</button>
                </form>
            </div>
            <form action="analytics.php?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" method="POST" style="display:inline;">
                <button type="submit" name="export_csv" class="export-btn">Export as CSV</button>
            </form>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Search Items</th>
                        <th>Search Date</th>
                        <th>Total Searches</th>
                        <th>Last Time Searched</th>
                        <th>Previous Search Date</th>
                        <th>Previous Search Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $is_current_date = ($row['SearchDate'] === $current_date) ? 'highlight-current' : '';
                            echo "<tr class='$is_current_date'>";
                            echo "<td>" . htmlspecialchars($row['SearchQuery']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['SearchDate']) . "</td>";
                            echo "<td>" . $row['TotalSearches'] . "</td>";
                            echo "<td>" . date('h:i:s A', strtotime($row['LastSearchTime'])) . "</td>";
                            echo "<td>" . ($row['PrevSearchDate'] ? htmlspecialchars($row['PrevSearchDate']) : 'N/A') . "</td>";
                            echo "<td>" . ($row['PrevSearchCount'] ?? 'N/A') . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo '<tr><td colspan="6">No search data available for the selected range.</td></tr>';
                    }
                    $stmt->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        const sidebar = document.querySelector('.sidebar');
        const hamburger = document.querySelector('.hamburger');
        hamburger.addEventListener('click', function() {
            sidebar.classList.toggle('hidden');
        });

        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname.split('/').pop();
            console.log('Current Path:', currentPath);
            document.querySelectorAll('.sidebar a').forEach(link => {
                const linkPath = link.getAttribute('href').split('/').pop();
                if (linkPath === currentPath) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>