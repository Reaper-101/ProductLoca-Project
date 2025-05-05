<?php
session_start();
include '../conn/conn.php'; // Include database connection

// Check if the user is logged in and is a superadmin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'superadmin') {
    header("Location: ../login.html");
    exit();
}

// Fetch all businesses
$sql = "SELECT BusinessID, BusinessName, Visibility FROM businessinfo";
$result = $conn->query($sql);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $business_id = intval($_POST['business_id']);
    $visibility = intval($_POST['visibility']);

    $update_sql = "UPDATE businessinfo SET Visibility = $visibility WHERE BusinessID = $business_id";
    if ($conn->query($update_sql)) {
        echo "<script>alert('Business visibility updated successfully.'); window.location.href = 'manage_business_visibility.php';</script>";
    } else {
        echo "<script>alert('Error updating visibility: " . $conn->error . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Business Visibility</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 800px; margin: 50px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        .back-button {
                    text-align: right;
                     margin-top: 20px;
                    }

        .btn-back {
                       padding: 10px 20px;
                       background-color: #ff7200;
                       color: white;
                       border: none;
                       border-radius: 5px;
                       text-decoration: none;
                       display: inline-block;
                    }

        .btn-back:hover {
                       background-color: #e56717;
                    }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table th, table td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        table th { background-color: #ff7200; color: #fff; }
        .btn { padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-visible { background-color: green; color: #fff; }
        .btn-hidden { background-color: red; color: #fff; }
    </style>
</head>
<body>
<div class="container">
    <h1>Manage Business Visibility</h1>
    <p>Toggle the visibility of businesses to control whether their products are displayed on the system.</p>
    <table>
        <thead>
            <tr>
                <th>Business Name</th>
                <th>Visibility</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['BusinessName']); ?></td>
                    <td><?php echo $row['Visibility'] ? 'Visible' : 'Hidden'; ?></td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="business_id" value="<?php echo $row['BusinessID']; ?>">
                            <?php if ($row['Visibility']): ?>
                                <button type="submit" name="visibility" value="0" class="btn btn-hidden">Hide</button>
                            <?php else: ?>
                                <button type="submit" name="visibility" value="1" class="btn btn-visible">Show</button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <div class="back-button">
                     <a href="superadmin_dashboard.php" class="btn-back">Back to Dashboard</a>
    </div>
</div>
</body>
</html>
