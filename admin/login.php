<?php
session_start();
include "../dbconnect.php";

$error = '';
$success = '';

// Check if request is JSON API
$isJson = (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
    || (isset($_POST['format']) && $_POST['format'] === 'json');

if (isset($_SESSION['user'])) {
    if ($isJson) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Already logged in', 'user' => $_SESSION['user']]);
        exit();
    }
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? ($_POST['login_username'] ?? ''));
    $password = trim($_POST['password'] ?? ($_POST['login_password'] ?? ''));

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
        if ($isJson) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $error]);
            exit();
        }
    } elseif (!$con) {
        $error = "Database connection offline. Please check Aiven credentials in db_setup.php.";
        if ($isJson) {
            header('Content-Type: application/json');
            http_response_code(503);
            echo json_encode(['status' => 'error', 'message' => $error]);
            exit();
        }
    } else {
        $u = mysqli_real_escape_string($con, $username);
        $p = mysqli_real_escape_string($con, $password);
        $res = mysqli_query($con, "SELECT * FROM users WHERE UserName = '$u' AND Password = '$p'");

        if ($res && $res instanceof mysqli_result && mysqli_num_rows($res) > 0) {
            $row = mysqli_fetch_assoc($res);
            $_SESSION['user'] = $row['UserName'];
            $_SESSION['is_admin'] = true;

            if ($isJson) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'success', 'message' => 'Admin login successful', 'user' => $row['UserName']]);
                exit();
            }
            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid admin credentials.";
            if ($isJson) {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode(['status' => 'error', 'message' => $error]);
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login - Online Book Shop</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/my.css" rel="stylesheet">
    <style>
        body { background: #2c3e50; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .admin-login-card {
            background: #fff;
            padding: 35px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 420px;
        }
        .admin-login-card h3 {
            color: #d35400;
            font-weight: 700;
            text-align: center;
            margin-bottom: 25px;
        }
        .btn-admin {
            background: #d35400;
            color: #fff;
            font-weight: 600;
            border: none;
        }
        .btn-admin:hover { background: #b04300; color: #fff; }
    </style>
</head>
<body>
    <div class="admin-login-card">
        <h3><span class="glyphicon glyphicon-lock"></span> Admin Portal</h3>
        <p class="text-center text-muted">Online Book Shop System</p>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post" action="login.php">
            <div class="form-group">
                <label>Admin Username</label>
                <input type="text" name="username" class="form-control" placeholder="Default: suyash" required autofocus>
            </div>
            <div class="form-group">
                <label>Admin Password</label>
                <input type="password" name="password" class="form-control" placeholder="Default: gulati" required>
            </div>
            <button type="submit" class="btn btn-admin btn-block btn-lg">Log In as Admin</button>
        </form>

        <hr>
        <div class="text-center">
            <a href="../index.php" class="text-muted"><span class="glyphicon glyphicon-arrow-left"></span> Back to Store</a>
            &nbsp;|&nbsp;
            <a href="../db_setup.php" class="text-muted"><span class="glyphicon glyphicon-wrench"></span> DB Setup</a>
        </div>
    </div>
</body>
</html>
