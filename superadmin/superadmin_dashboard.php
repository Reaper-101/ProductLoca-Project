<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include '../conn/conn.php'; // Include the database connection

// Check if the user is logged in and is a superadmin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'superadmin') {
    header("Location: ../login.html"); // Redirect to login page if not a superadmin
    exit();
}

// Get the logged-in user's ID
$user_id = (int)$_SESSION['user_id'];

// Fetch the store manager's first name with a prepared statement
$sql_user = "SELECT UserFirstName FROM UserAccount WHERE UserAccountID = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();

if ($result_user->num_rows > 0) {
    $user = $result_user->fetch_assoc();
    $first_name = $user['UserFirstName'];
} else {
    $first_name = "User"; // Fallback if no first name is found
}
$stmt_user->close();

// Handle unlock request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['unlock_username'])) {
    $unlock_username = $_POST['unlock_username'];
    $stmt = $conn->prepare("UPDATE login_attempts SET LockedUntil = NULL WHERE Username = ?");
    $stmt->bind_param("s", $unlock_username);
    $stmt->execute();
    $stmt->close();
    $unlock_message = "Account '$unlock_username' unlocked successfully.";
}

// Fetch locked accounts
$stmt = $conn->prepare("SELECT DISTINCT Username FROM login_attempts WHERE LockedUntil IS NOT NULL AND LockedUntil > NOW()");
$stmt->execute();
$result = $stmt->get_result();
$locked_accounts = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard</title>
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
        }
        .sidebar.hidden ~ .content { margin-left: 0; border-radius: 20px; }

        h1 { color: #023047; font-size: 28px; font-weight: 600; margin-bottom: 10px; }
        p { color: #666; font-size: 16px; margin-bottom: 30px; }
        p strong { color: #ff7200; }

        /* Unlock Section */
        .unlock-section { margin-top: 20px; }
        .unlock-section h3 { color: #023047; font-size: 20px; font-weight: 600; margin-bottom: 15px; }
        .unlock-section form { display: flex; gap: 10px; align-items: center; margin-bottom: 20px; }
        .unlock-section input[type="text"] { padding: 8px; width: 200px; border-radius: 5px; border: 1px solid #ddd; font-size: 14px; }
        .unlock-section button { padding: 8px 15px; background-color: #ff7200; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; }
        .unlock-section button:hover { background-color: #ff8c00; }
        .unlock-section .message { color: #28a745; font-weight: 500; margin-top: 10px; }
        .unlock-section .locked-list h4 { color: #023047; font-size: 18px; margin-bottom: 10px; }
        .unlock-section .locked-list ul { list-style: none; padding: 0; }
        .unlock-section .locked-list li { padding: 8px; background: #f9f9f9; margin: 5px 0; border-radius: 5px; color: #333; }
        .unlock-section .locked-list li:hover { background: #f1f1f1; }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .content { margin-left: 250px; padding: 30px; }
        }
        @media (max-width: 768px) {
            .content { margin-left: 0; padding: 20px; border-radius: 10px; }
            .hamburger { left: 15px; top: 15px; }
            .unlock-section form { flex-direction: column; }
            .unlock-section input[type="text"] { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="hamburger">
        <i class="fas fa-bars"></i>
    </div>

    <!-- Inline Superadmin Sidebar -->
    <div class="sidebar">
        <div class="profile">
            <i class="fas fa-user-shield"></i>
            <h2><?php echo htmlspecialchars($_SESSION['username']); ?></h2>
        </div>
        <a href="superadmin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="manage_business.php"><i class="fas fa-building"></i> Manage Business</a>
        <!-- <a href="manage_business_visibility.php"><i class="fas fa-eye"></i> Manage Business Visibility</a> -->
        <a href="manage_admins.php"><i class="fas fa-users-cog"></i> Manage Accounts</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="content">
        <h1>Super Admin Dashboard</h1>
        <p>Welcome, <strong><?php echo htmlspecialchars($first_name); ?></strong>, to your superadmin dashboard.</p>

        <!-- Unlock Accounts Section -->
        <div class="unlock-section">
            <h3>Unlock Accounts</h3>
            <form method="POST">
                <input type="text" name="unlock_username" placeholder="Enter username to unlock" required>
                <button type="submit">Unlock</button>
            </form>
            <?php if (isset($unlock_message)): ?>
                <p class="message"><?php echo htmlspecialchars($unlock_message); ?></p>
            <?php endif; ?>
            <div class="locked-list">
                <h4>Currently Locked Accounts:</h4>
                <?php if (empty($locked_accounts)): ?>
                    <p>No accounts are currently locked.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($locked_accounts as $account): ?>
                            <li><?php echo htmlspecialchars($account['Username']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
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