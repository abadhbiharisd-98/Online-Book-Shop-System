<?php
session_start();
include "../dbconnect.php";

// Protect admin access: redirect with direct file routing to avoid directory trailing slash bug
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$currentUser = $_SESSION['user'];

// Database statistics with null-safe checks
$totalProducts = 0;
$totalUsers = 0;
$totalCarts = 0;
$dbStatus = "Connected";
$dbHost = defined('DB_HOST') ? DB_HOST : 'Unknown';
$dbName = defined('DB_NAME') ? DB_NAME : 'Unknown';

if ($con) {
    $resP = mysqli_query($con, "SELECT COUNT(*) AS cnt FROM products");
    if ($resP && $resP instanceof mysqli_result) {
        $rowP = mysqli_fetch_assoc($resP);
        $totalProducts = $rowP['cnt'] ?? 0;
    }
    $resU = mysqli_query($con, "SELECT COUNT(*) AS cnt FROM users");
    if ($resU && $resU instanceof mysqli_result) {
        $rowU = mysqli_fetch_assoc($resU);
        $totalUsers = $rowU['cnt'] ?? 0;
    }
    $resC = mysqli_query($con, "SELECT COUNT(*) AS cnt FROM cart");
    if ($resC && $resC instanceof mysqli_result) {
        $rowC = mysqli_fetch_assoc($resC);
        $totalCarts = $rowC['cnt'] ?? 0;
    }
} else {
    $dbStatus = "Offline / Not Connected";
}

// Fetch products list
$products = [];
if ($con) {
    $res = mysqli_query($con, "SELECT PID, Title, Author, Price, Discount, Available, Category FROM products ORDER BY PID LIMIT 50");
    if ($res && $res instanceof mysqli_result) {
        while ($row = mysqli_fetch_assoc($res)) {
            $products[] = $row;
        }
    }
}

// Fetch users list
$users = [];
if ($con) {
    $res = mysqli_query($con, "SELECT UserName FROM users ORDER BY UserName LIMIT 50");
    if ($res && $res instanceof mysqli_result) {
        while ($row = mysqli_fetch_assoc($res)) {
            $users[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard - Online Book Shop</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/my.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .navbar-admin { background: #2c3e50; border-color: #1a252f; border-radius: 0; }
        .navbar-admin .navbar-brand, .navbar-admin .navbar-nav>li>a { color: #ecf0f1; }
        .navbar-admin .navbar-nav>li>a:hover { color: #f39c12; }
        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            border-left: 5px solid #d35400;
        }
        .stat-card.blue { border-left-color: #2980b9; }
        .stat-card.green { border-left-color: #27ae60; }
        .stat-card.purple { border-left-color: #8e44ad; }
        .stat-num { font-size: 28px; font-weight: 700; margin: 10px 0 0 0; }
        .content-box {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-admin">
        <div class="container-fluid">
            <div class="navbar-header">
                <a class="navbar-brand" href="index.php"><span class="glyphicon glyphicon-dashboard"></span> Admin Dashboard</a>
            </div>
            <ul class="nav navbar-nav navbar-right">
                <li><a href="../index.php" target="_blank"><span class="glyphicon glyphicon-eye-open"></span> View Store</a></li>
                <li><a href="../db_setup.php"><span class="glyphicon glyphicon-wrench"></span> DB Setup Tool</a></li>
                <li><a href="#" style="color:#f39c12;"><span class="glyphicon glyphicon-user"></span> <?php echo htmlspecialchars($currentUser); ?></a></li>
                <li><a href="../destroy.php"><span class="glyphicon glyphicon-log-out"></span> Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container-fluid" style="padding: 20px 40px;">
        <!-- Stats Row -->
        <div class="row">
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <span class="text-muted"><span class="glyphicon glyphicon-book"></span> Total Products</span>
                    <p class="stat-num text-danger"><?php echo (int)$totalProducts; ?></p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card blue">
                    <span class="text-muted"><span class="glyphicon glyphicon-user"></span> Registered Users</span>
                    <p class="stat-num text-primary"><?php echo (int)$totalUsers; ?></p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card green">
                    <span class="text-muted"><span class="glyphicon glyphicon-shopping-cart"></span> Cart Orders</span>
                    <p class="stat-num text-success"><?php echo (int)$totalCarts; ?></p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card purple">
                    <span class="text-muted"><span class="glyphicon glyphicon-hdd"></span> Database</span>
                    <p class="stat-num" style="font-size:18px; padding-top:8px; color:<?php echo $con ? '#27ae60' : '#e74c3c'; ?>;">
                        <?php echo $dbStatus; ?>
                    </p>
                    <small class="text-muted"><?php echo htmlspecialchars($dbHost); ?> (<?php echo htmlspecialchars($dbName); ?>)</small>
                </div>
            </div>
        </div>

        <!-- Quick actions -->
        <div class="row">
            <div class="col-md-12">
                <div class="content-box">
                    <h4><span class="glyphicon glyphicon-flash"></span> Quick Deployment Actions</h4>
                    <p class="text-muted">Direct links designed for Render & Aiven Cloud operations:</p>
                    <a href="../db_setup.php" class="btn btn-info btn-md"><span class="glyphicon glyphicon-wrench"></span> Open 1-Click Aiven DB Importer</a>
                    <a href="../index.php" class="btn btn-default btn-md" style="margin-left: 10px;"><span class="glyphicon glyphicon-home"></span> Open Main Store Front</a>
                    <a href="login.php" class="btn btn-default btn-md" style="margin-left: 10px;"><span class="glyphicon glyphicon-lock"></span> Admin Login Endpoint (Direct File)</a>
                </div>
            </div>
        </div>

        <!-- Products Table -->
        <div class="row">
            <div class="col-md-9">
                <div class="content-box">
                    <h4><span class="glyphicon glyphicon-list"></span> Product Catalog (<?php echo count($products); ?> items)</h4>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>PID</th>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Discount</th>
                                    <th>Available</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($products)): ?>
                                    <tr><td colspan="7" class="text-center text-muted">No products found. Please use the <a href="../db_setup.php">DB Setup Tool</a> to import the bookstore.sql dump.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($products as $p): ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($p['PID']); ?></code></td>
                                            <td><?php echo htmlspecialchars($p['Title']); ?></td>
                                            <td><?php echo htmlspecialchars($p['Author']); ?></td>
                                            <td><span class="label label-info"><?php echo htmlspecialchars($p['Category']); ?></span></td>
                                            <td>&#8377; <?php echo htmlspecialchars($p['Price']); ?></td>
                                            <td><?php echo htmlspecialchars($p['Discount']); ?>%</td>
                                            <td><?php echo (int)$p['Available']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="col-md-3">
                <div class="content-box">
                    <h4><span class="glyphicon glyphicon-user"></span> System Users</h4>
                    <ul class="list-group">
                        <?php if (empty($users)): ?>
                            <li class="list-group-item text-muted">No users found</li>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                                <li class="list-group-item">
                                    <span class="glyphicon glyphicon-ok-sign text-success"></span>
                                    <strong><?php echo htmlspecialchars($u['UserName']); ?></strong>
                                    <?php if ($u['UserName'] === 'suyash'): ?>
                                        <span class="label label-primary pull-right">Admin</span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
</body>
</html>
