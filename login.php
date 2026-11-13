<?php
session_start();
include "dbconnect.php";

// Check if user is already logged in
if (isset($_SESSION['user']) && !isset($_GET['action'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

// Check if request is JSON / API
$isJsonRequest = (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
    || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)
    || (isset($_POST['format']) && $_POST['format'] === 'json')
    || (isset($_GET['format']) && $_GET['format'] === 'json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read raw JSON if sent
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $jsonData = json_decode($raw, true) ?: [];
        $username = trim($jsonData['username'] ?? ($jsonData['login_username'] ?? ''));
        $password = trim($jsonData['password'] ?? ($jsonData['login_password'] ?? ''));
    } else {
        $username = trim($_POST['login_username'] ?? ($_POST['username'] ?? ''));
        $password = trim($_POST['login_password'] ?? ($_POST['password'] ?? ''));
    }

    if ($username === '' || $password === '') {
        $error = "Please enter both username and password.";
        if ($isJsonRequest) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $error]);
            exit();
        }
    } else {
        if (!$con) {
            $error = "Database connection error. Please configure database credentials.";
            if ($isJsonRequest) {
                header('Content-Type: application/json');
                http_response_code(503);
                echo json_encode(['status' => 'error', 'message' => $error]);
                exit();
            }
        } else {
            $cleanUser = mysqli_real_escape_string($con, $username);
            $cleanPass = mysqli_real_escape_string($con, $password);
            $query = "SELECT * FROM users WHERE UserName = '$cleanUser' AND Password = '$cleanPass'";
            $result = mysqli_query($con, $query);

            if ($result && $result instanceof mysqli_result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                $_SESSION['user'] = $row['UserName'];
                
                if ($isJsonRequest) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Successfully Logged In',
                        'user' => $row['UserName']
                    ]);
                    exit();
                }
                header("Location: index.php?Message=" . urlencode("Successfully Logged In"));
                exit();
            } else {
                $error = "Incorrect username or password.";
                if ($isJsonRequest) {
                    header('Content-Type: application/json');
                    http_response_code(401);
                    echo json_encode(['status' => 'error', 'message' => $error]);
                    exit();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Online Book Shop</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/my.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; }
        .login-box {
            max-width: 420px;
            margin: 80px auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .login-box h3 {
            color: #D67B22;
            font-weight: bold;
            margin-bottom: 25px;
            text-align: center;
        }
        .btn-custom {
            background-color: #D67B22;
            color: white;
            font-weight: bold;
            border: none;
        }
        .btn-custom:hover { background-color: #b56315; color: white; }
    </style>
</head>
<body>
    <nav class="navbar navbar-default navbar-fixed-top navbar-inverse">
        <div class="container-fluid">
            <div class="navbar-header">
                <a class="navbar-brand" href="index.php"><img alt="Brand" src="img/logo.jpg" style="width: 118px;margin-top: -7px;margin-left: -10px;"></a>
            </div>
            <ul class="nav navbar-nav navbar-right">
                <li><a href="index.php"><span class="glyphicon glyphicon-home"></span> Home</a></li>
                <li><a href="admin/login.php"><span class="glyphicon glyphicon-lock"></span> Admin</a></li>
                <li><a href="db_setup.php"><span class="glyphicon glyphicon-wrench"></span> DB Setup</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="login-box">
            <h3>Online Book Shop Login</h3>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="post" action="login.php">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" name="login_username" id="username" class="form-control" placeholder="Enter username" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="login_password" id="password" class="form-control" placeholder="Enter password" required>
                </div>
                <button type="submit" name="submit" value="login" class="btn btn-custom btn-block btn-lg">Sign In</button>
            </form>
            
            <hr>
            <div class="text-center text-muted">
                <small>Default User: <strong>suyash</strong> / Password: <strong>gulati</strong></small><br>
                <small>Need to import database? <a href="db_setup.php">Click here for 1-Click DB Setup</a></small>
            </div>
        </div>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
</body>
</html>
