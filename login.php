<?php
session_start([
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'use_strict_mode' => true
]);

include 'conn/conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'];

    if (empty($username) || empty($password)) {
        echo "<script>alert('Please enter both username and password.'); window.location.href = 'login.html';</script>";
        exit();
    }

    $stmt = $conn->prepare("SELECT LockedUntil FROM login_attempts WHERE Username = ? AND IP = ? ORDER BY AttemptTime DESC LIMIT 1");
    $stmt->bind_param("ss", $username, $ip);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $lock_data = $result->fetch_assoc();
        if ($lock_data['LockedUntil'] !== null && strtotime($lock_data['LockedUntil']) > time()) {
            echo "<script>alert('Your account is locked. Please contact your administrator.'); window.location.href = 'login.html?locked=1';</script>";
            exit();
        }
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) FROM login_attempts WHERE Username = ? AND IP = ? AND AttemptTime > NOW() - INTERVAL 60 SECOND AND LockedUntil IS NULL");
    $stmt->bind_param("ss", $username, $ip);
    $stmt->execute();
    $result = $stmt->get_result();
    $recent_attempts = $result->fetch_row()[0];
    $stmt->close();

    if ($recent_attempts >= 3) {
        echo "<script>alert('Too many failed attempts. Login disabled for 60 seconds.'); window.location.href = 'login.html?lockout=1&remaining=60';</script>";
        exit();
    }

    $stmt = $conn->prepare("SELECT COUNT(*) FROM login_attempts WHERE Username = ? AND IP = ? AND LockedUntil IS NULL");
    $stmt->bind_param("ss", $username, $ip);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_attempts = $result->fetch_row()[0];
    $stmt->close();

    try {
        $stmt = $conn->prepare("SELECT * FROM useraccount WHERE Username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if ($user['status'] == 'inactive') {
                echo "<script>alert('Your account is inactive. Please contact the administrator.'); window.location.href = 'login.html';</script>";
                exit();
            }

            if (password_verify($password, $user['Password'])) {
                $stmt = $conn->prepare("DELETE FROM login_attempts WHERE Username = ? AND IP = ?");
                $stmt->bind_param("ss", $username, $ip);
                $stmt->execute();
                $stmt->close();

                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['UserAccountID'];
                $_SESSION['username'] = $user['Username'];
                $_SESSION['user_first_name'] = $user['UserFirstName'];
                $_SESSION['user_type'] = $user['UserType'];
                $_SESSION['business_id'] = $user['BusinessID'];
                $_SESSION['store_id'] = $user['StoreID'];

                switch ($user['UserType']) {
                    case 'admin':
                        $location = $user['BusinessID'] != null ? 'admin/admin_dashboard.php' : 'admin/no_business_assigned.php';
                        break;
                    case 'store_manager':
                        $location = $user['StoreID'] != null ? 'storemanager/store_manager_dashboard.php' : 'storemanager/no_store_assigned.php';
                        break;
                    case 'superadmin':
                        $location = 'superadmin/superadmin_dashboard.php';
                        break;
                    default:
                        $location = 'customer_dashboard.php';
                        break;
                }
                header("Location: $location");
                exit();
            } else {
                $stmt = $conn->prepare("INSERT INTO login_attempts (Username, AttemptTime, IP) VALUES (?, NOW(), ?)");
                $stmt->bind_param("ss", $username, $ip);
                $stmt->execute();
                $stmt->close();

                $new_total_attempts = $total_attempts + 1;
                $new_recent_attempts = $recent_attempts + 1;

                if ($new_total_attempts >= 6) {
                    $stmt = $conn->prepare("UPDATE login_attempts SET LockedUntil = '9999-12-31 23:59:59' WHERE Username = ? AND IP = ? AND LockedUntil IS NULL");
                    $stmt->bind_param("ss", $username, $ip);
                    $stmt->execute();
                    $stmt->close();
                    echo "<script>alert('Your account is locked due to multiple failed attempts. Please contact your administrator.'); window.location.href = 'login.html?locked=1';</script>";
                } elseif ($new_recent_attempts >= 3) {
                    echo "<script>alert('Too many failed attempts. Login disabled for 60 seconds.'); window.location.href = 'login.html?lockout=1&remaining=60';</script>";
                } else {
                    echo "<script>alert('Invalid username or password. Attempt $new_recent_attempts of 3 (Total: $new_total_attempts).'); window.location.href = 'login.html';</script>";
                }
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO login_attempts (Username, AttemptTime, IP) VALUES (?, NOW(), ?)");
            $stmt->bind_param("ss", $username, $ip);
            $stmt->execute();
            $stmt->close();

            $new_total_attempts = $total_attempts + 1;
            $new_recent_attempts = $recent_attempts + 1;

            if ($new_total_attempts >= 6) {
                $stmt = $conn->prepare("UPDATE login_attempts SET LockedUntil = '9999-12-31 23:59:59' WHERE Username = ? AND IP = ? AND LockedUntil IS NULL");
                $stmt->bind_param("ss", $username, $ip);
                $stmt->execute();
                $stmt->close();
                echo "<script>alert('Your account is locked due to multiple failed attempts. Please contact your administrator.'); window.location.href = 'login.html?locked=1';</script>";
            } elseif ($new_recent_attempts >= 3) {
                echo "<script>alert('Too many failed attempts. Login disabled for 60 seconds.'); window.location.href = 'login.html?lockout=1&remaining=60';</script>";
            } else {
                echo "<script>alert('No user found with that username. Attempt $new_recent_attempts of 3 (Total: $new_total_attempts).'); window.location.href = 'login.html';</script>";
            }
        }
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        echo "<script>alert('An error occurred. Please try again later.'); window.location.href = 'login.html';</script>";
    }
}

$conn->close();
?>