<?php
session_start();
include "dbconnect.php";

$error = '';
$success = '';

if (isset($_POST['submit']) && $_POST['submit'] == "register") {
    $username = isset($_POST['register_username']) ? trim($_POST['register_username']) : '';
    $password = isset($_POST['register_password']) ? trim($_POST['register_password']) : '';

    if ($username === '' || $password === '') {
        $error = "Please provide both username and password.";
    } elseif (!$con) {
        $error = "Database connection unavailable. Please check database settings.";
    } else {
        $cleanUser = mysqli_real_escape_string($con, $username);
        $cleanPass = mysqli_real_escape_string($con, $password);
        $checkQuery = "SELECT * FROM users WHERE UserName = '$cleanUser'";
        $result = mysqli_query($con, $checkQuery);

        if ($result && $result instanceof mysqli_result && mysqli_num_rows($result) > 0) {
            $error = "Username is already taken. Please choose a different username.";
        } else {
            $insertQuery = "INSERT INTO users (UserName, Password) VALUES ('$cleanUser', '$cleanPass')";
            $insertResult = mysqli_query($con, $insertQuery);
            if ($insertResult) {
                $_SESSION['user'] = $cleanUser;
                header("Location: index.php?Message=" . urlencode("Successfully Registered & Logged In!"));
                exit();
            } else {
                $error = "Registration failed: " . mysqli_error($con);
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
    <title>Register - Online Book Shop</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/my.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; }
        .reg-box {
            max-width: 420px;
            margin: 80px auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .reg-box h3 {
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
                <li><a href="login.php"><span class="glyphicon glyphicon-log-in"></span> Login</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="reg-box">
            <h3>Create an Account</h3>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="post" action="register.php">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" name="register_username" id="username" class="form-control" placeholder="Choose a username" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="register_password" id="password" class="form-control" placeholder="Choose a password" required>
                </div>
                <button type="submit" name="submit" value="register" class="btn btn-custom btn-block btn-lg">Sign Up</button>
            </form>
            <hr>
            <div class="text-center">
                <small>Already registered? <a href="login.php">Click here to Sign In</a></small>
            </div>
        </div>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
</body>
</html>
