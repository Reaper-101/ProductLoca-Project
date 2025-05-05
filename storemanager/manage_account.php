<?php
session_start();
include '../conn/conn.php'; // Include the database connection

// Check if the user is logged in and is a store manager
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'store_manager') {
    header("Location: ../login.html"); // Redirect to login if not a store manager
    exit();
}

// Get the logged-in user's ID
$user_id = (int)$_SESSION['user_id'];

// Fetch the store manager's account details with prepared statement
$sql_user = "SELECT UserFirstName, UserLastName, PhoneNumber, Email, Username FROM UserAccount WHERE UserAccountID = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();

if ($result_user->num_rows > 0) {
    $user = $result_user->fetch_assoc();
} else {
    echo "<script>alert('User not found.'); window.location.href = 'store_manager_dashboard.php';</script>";
    exit();
}
$stmt_user->close();

// Handle form submission for updating account details
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $contact_number = $_POST['contact_number'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : null;

    // Prepare the update query based on whether password is provided
    if ($password) {
        $sql_update = "UPDATE UserAccount SET UserFirstName = ?, UserLastName = ?, PhoneNumber = ?, Email = ?, Username = ?, Password = ? WHERE UserAccountID = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ssssssi", $first_name, $last_name, $contact_number, $email, $username, $password, $user_id);
    } else {
        $sql_update = "UPDATE UserAccount SET UserFirstName = ?, UserLastName = ?, PhoneNumber = ?, Email = ?, Username = ? WHERE UserAccountID = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("sssssi", $first_name, $last_name, $contact_number, $email, $username, $user_id);
    }

    // Execute the update query
    if ($stmt_update->execute()) {
        $_SESSION['username'] = $username; // Update session username
        echo "<script>alert('Account updated successfully!'); window.location.href = 'manage_account.php';</script>";
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
    <title>Manage Account</title>
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

        /* Sidebar */
        .sidebar {
            position: fixed;
            height: 100vh;
            width: 280px;
            background: linear-gradient(180deg, #023047 0%, #1b263b 100%);
            display: flex;
            flex-direction: column;
            padding: 20px 15px;
            color: #fff;
            transition: all 0.3s ease;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }
        .sidebar.hidden { transform: translateX(-100%); }
        .sidebar .profile {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .sidebar .profile i { font-size: 50px; color: #ff7200; margin-bottom: 10px; }
        .sidebar .profile h2 { font-size: 20px; color: #fff; font-weight: 500; }
        .sidebar a {
            padding: 15px;
            text-decoration: none;
            font-size: 16px;
            color: #fff;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            border-radius: 8px;
            margin: 5px 0;
        }
        .sidebar a:hover, .sidebar a.active {
            background: rgba(255, 114, 0, 0.2);
            color: #ff7200;
            transform: translateX(5px);
        }
        .sidebar a i { margin-right: 12px; font-size: 18px; }

        /* Content */
        .content {
            margin-left: 280px;
            padding: 40px;
            transition: all 0.3s ease;
            min-height: 100vh;
            background: #fff;
            border-radius: 20px 0 0 20px;
            box-shadow: -5px 0 15px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .sidebar.hidden ~ .content { margin-left: 0; border-radius: 20px; }

        .container {
            width: 600px;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 { color: #023047; font-size: 24px; font-weight: 600; text-align: center; margin-bottom: 20px; }
        label { color: #666; font-size: 14px; display: block; margin: 5px 0; }
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-size: 14px;
        }
        .button-group { display: flex; justify-content: space-between; margin-top: 20px; }
        .button-group button {
            width: 48%;
            padding: 10px;
            background-color: #ff7200;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .button-group button:hover { background-color: #ff8c00; }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .content { margin-left: 250px; padding: 30px; }
            .container { width: 100%; }
        }
        @media (max-width: 768px) {
            .content { margin-left: 0; padding: 20px; border-radius: 10px; }
            .hamburger { left: 15px; top: 15px; }
            .button-group { flex-direction: column; gap: 10px; }
            .button-group button { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="hamburger">
        <i class="fas fa-bars"></i>
    </div>

    <!-- Inline Store Manager Sidebar -->
    <div class="sidebar">
        <div class="profile">
            <i class="fas fa-user-tie"></i>
            <h2><?php echo htmlspecialchars($_SESSION['username']); ?></h2>
        </div>
        <a href="store_manager_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="manage_account.php"><i class="fas fa-user-cog"></i> Manage Account</a>
        <a href="manage_product.php"><i class="fas fa-box-open"></i> Manage Products</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="content">
        <div class="container">
            <h2>Manage Account</h2>
            <!-- Account Update Form -->
            <form method="POST" action="manage_account.php">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['UserFirstName']); ?>" required>

                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['UserLastName']); ?>" required>

                <label for="contact_number">Contact Number</label>
                <input type="text" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($user['PhoneNumber']); ?>" required>

                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['Email']); ?>" required>

                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['Username']); ?>" required>

                <label for="password">Password (Leave blank if unchanged)</label>
                <input type="password" id="password" name="password">

                <div class="button-group">
                    <button type="button" onclick="window.location.href='store_manager_dashboard.php';">Back</button>
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