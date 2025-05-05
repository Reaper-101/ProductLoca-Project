<?php
session_start();
include '../conn/conn.php'; // Include the database connection

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.html");
    exit();
}

$admin_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
if (!$admin_id) {
    echo "<script>alert('Invalid session. Please log in again.'); window.location.href = '../login.html';</script>";
    exit();
}

// Fetch the admin's account details
$sql = "SELECT * FROM UserAccount WHERE UserAccountID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $admin = $result->fetch_assoc();
} else {
    echo "<script>alert('No admin found!'); window.location.href = 'admin_dashboard.php';</script>";
    exit();
}
$stmt->close();

// Handle form submission to update account details
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $contact_number = $conn->real_escape_string($_POST['contact_number']);
    $email = $conn->real_escape_string($_POST['email']);
    $username = $conn->real_escape_string($_POST['username']);

    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $sql_update = "UPDATE UserAccount SET UserFirstName = ?, UserLastName = ?, PhoneNumber = ?, Email = ?, Username = ?, Password = ? WHERE UserAccountID = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ssssssi", $first_name, $last_name, $contact_number, $email, $username, $password, $admin_id);
    } else {
        $sql_update = "UPDATE UserAccount SET UserFirstName = ?, UserLastName = ?, PhoneNumber = ?, Email = ?, Username = ? WHERE UserAccountID = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("sssssi", $first_name, $last_name, $contact_number, $email, $username, $admin_id);
    }

    if ($stmt_update->execute()) {
        $_SESSION['username'] = $username; // Update session username if changed
        echo "<script>alert('Account updated successfully!'); window.location.href = 'admin_dashboard.php';</script>";
    } else {
        echo "<script>alert('Error updating account: " . $stmt_update->error . "');</script>";
    }
    $stmt_update->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Account - <?php echo htmlspecialchars($admin['Username']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; }

        /* Hamburger */
        .hamburger {
            font-size: 28px;
            position: fixed;
            top: 20px;
            left: 20px;
            cursor: pointer;
            z-index: 1000;
            color: #023047;
            transition: all 0.3s ease;
        }
        .sidebar.hidden ~ .hamburger { left: 20px; }

        /* Content */
        .content {
            margin-left: 280px;
            padding: 40px;
            transition: all 0.3s ease;
            min-height: 100vh;
            background: #fff;
            border-radius: 20px 0 0 20px;
            box-shadow: -5px 0 15px rgba(0, 0, 0, 0.05);
        }
        .sidebar.hidden ~ .content { margin-left: 0; border-radius: 20px; }

        /* Form Container */
        .form-container {
            max-width: 600px;
            margin: 0 auto;
            background: #023047;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            color: #fff;
        }
        .form-container h2 {
            text-align: center;
            color: #ff7200;
            margin-bottom: 20px;
            font-size: 24px;
            font-weight: 600;
        }
        .form-container label {
            display: block;
            margin: 10px 0 5px;
            font-size: 16px;
            font-weight: 500;
        }
        .form-container input[type="text"],
        .form-container input[type="email"],
        .form-container input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 14px;
        }
        .form-container .button-group {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .form-container button {
            width: 48%;
            padding: 10px;
            background-color: #ff7200;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }
        .form-container button:hover {
            background-color: #ff8c00;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .content { margin-left: 0; padding: 20px; border-radius: 10px; }
            .form-container { padding: 20px; }
            .hamburger { left: 15px; top: 15px; }
        }
        @media (max-width: 480px) {
            .form-container h2 { font-size: 20px; }
            .form-container input[type="text"],
            .form-container input[type="email"],
            .form-container input[type="password"] { font-size: 12px; }
            .form-container button { font-size: 14px; }
        }
    </style>
</head>
<body>
    <div class="hamburger">
        <i class="fas fa-bars"></i>
    </div>

    <?php include 'sidebar.php'; ?>

    <div class="content">
        <div class="form-container">
            <h2>Manage Account</h2>
            <form method="POST" action="manage_account.php">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($admin['UserFirstName']); ?>" required>

                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($admin['UserLastName']); ?>" required>

                <label for="contact_number">Contact Number</label>
                <input type="text" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($admin['PhoneNumber']); ?>" required>

                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($admin['Email']); ?>" required>

                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($admin['Username']); ?>" required>

                <label for="password">New Password (Leave blank to keep current password)</label>
                <input type="password" id="password" name="password">

                <div class="button-group">
                    <button type="button" onclick="window.location.href='admin_dashboard.php'">Back</button>
                    <button type="submit">Update Account</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Toggle sidebar
        const sidebar = document.querySelector('.sidebar');
        const hamburger = document.querySelector('.hamburger');

        hamburger.addEventListener('click', function() {
            sidebar.classList.toggle('hidden');
        });

        // Highlight active sidebar link
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname.split('/').pop();
            console.log('Current Path:', currentPath); // Debug URL
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