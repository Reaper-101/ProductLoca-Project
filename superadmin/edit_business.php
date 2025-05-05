<?php
session_start();
include '../conn/conn.php'; // Include the database connection

// Check if the user is logged in and is a superadmin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'superadmin') {
    header("Location: ../login.html");
    exit();
}

// Check if the `id` parameter is set in the URL
if (!isset($_GET['id'])) {
    echo "<script>alert('No Business ID specified.'); window.location.href = 'manage_business.php';</script>";
    exit();
}

$business_id = $conn->real_escape_string($_GET['id']);

// Fetch the business details based on the ID
$sql_business = "SELECT * FROM BusinessInfo WHERE BusinessID = '$business_id'";
$result_business = $conn->query($sql_business);

if ($result_business->num_rows != 1) {
    echo "<script>alert('Business account not found.'); window.location.href = 'manage_business.php';</script>";
    exit();
}

$business = $result_business->fetch_assoc();

// Handle form submission for updating the business details
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $businessName = $conn->real_escape_string($_POST['businessName']);
    $address = $conn->real_escape_string($_POST['address']);
    $businessEmail = $conn->real_escape_string($_POST['businessEmail']);
    $businessLicense = $conn->real_escape_string($_POST['businessLicense']);
    $birNumber = $conn->real_escape_string($_POST['birNumber']);
    $businessContactNum = $conn->real_escape_string($_POST['businessContactNum']);
    $status = $conn->real_escape_string($_POST['status']);

    // Update the business information in the BusinessInfo table
    $sql_update = "UPDATE BusinessInfo SET 
                   BusinessName = '$businessName', 
                   Address = '$address', 
                   BusinessEmail = '$businessEmail', 
                   BusinessLicense = '$businessLicense', 
                   BIRnumber = '$birNumber', 
                   BusinessContactNum = '$businessContactNum', 
                   status = '$status' 
                   WHERE BusinessID = '$business_id'";

    if ($conn->query($sql_update) === TRUE) {
        echo "<script>alert('Business information updated successfully!'); window.location.href = 'manage_business.php';</script>";
    } else {
        echo "<script>alert('Error updating business information: " . $conn->error . "');</script>";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Business Information</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #ffff;
        }
        .container {
            width: 500px;
            margin: 50px auto;
            background: #023047;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            color: #ffff;
        }
        input[type="text"], input[type="email"], textarea, select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        label {
            color: #ffff;
        }
        .button-group {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .button-group button, .button-group a {
            width: 48%;
            padding: 10px;
            background-color: #ff7200;
            color: #fff;
            text-align: center;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }
        .button-group button:hover, .button-group a:hover {
            background-color: #ff8c00;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Edit Business Information</h2>

    <form method="POST" action="edit_business.php?id=<?php echo $business['BusinessID']; ?>">
        <label for="businessName">Business Name:</label>
        <input type="text" id="businessName" name="businessName" value="<?php echo htmlspecialchars($business['BusinessName']); ?>" required>

        <label for="address">Address:</label>
        <textarea id="address" name="address" rows="4" required><?php echo htmlspecialchars($business['Address']); ?></textarea>

        <label for="businessEmail">Business Email:</label>
        <input type="email" id="businessEmail" name="businessEmail" value="<?php echo htmlspecialchars($business['BusinessEmail']); ?>" required>

        <label for="businessLicense">Business License:</label>
        <input type="text" id="businessLicense" name="businessLicense" value="<?php echo htmlspecialchars($business['BusinessLicense']); ?>" required>

        <label for="birNumber">BIR Number:</label>
        <input type="text" id="birNumber" name="birNumber" value="<?php echo htmlspecialchars($business['BIRnumber']); ?>" required>

        <label for="businessContactNum">Business Contact Number:</label>
        <input type="text" id="businessContactNum" name="businessContactNum" value="<?php echo htmlspecialchars($business['BusinessContactNum']); ?>" required>

        <label for="status">Account Status:</label>
        <select id="status" name="status" required>
            <option value="active" <?php echo $business['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
            <option value="disabled" <?php echo $business['status'] == 'disabled' ? 'selected' : ''; ?>>Disabled</option>
        </select>

        <div class="button-group">
            <a href="manage_business.php">Back</a>
            <button type="submit">Save Changes</button>
        </div>
    </form>
</div>

</body>
</html>
