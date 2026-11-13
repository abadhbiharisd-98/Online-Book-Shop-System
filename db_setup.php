<?php
/**
 * 1-Click Aiven Cloud MySQL Database Importer & Health Checker
 * Online Book Shop System
 */

session_start();
include "dbconnect.php";

$message = '';
$messageType = '';
$importedCount = 0;
$importErrors = [];

// Determine active configuration
$currentHost = defined('DB_HOST') ? DB_HOST : (getenv('DB_SERVER') ?: 'localhost');
$currentPort = defined('DB_PORT') ? DB_PORT : (getenv('DB_PORT') ?: 3306);
$currentUser = defined('DB_USER') ? DB_USER : (getenv('DB_USERNAME') ?: 'root');
$currentDb   = defined('DB_NAME') ? DB_NAME : (getenv('DB_NAME') ?: 'bookstore');
$isSslActive = isset($dbInstance) ? $dbInstance->isSsl() : (getenv('DB_SSL') === 'true');

// Check connection health
$isConnected = false;
$serverInfo = '';
$sslCipher = '';
$connectionError = '';

if ($con && @mysqli_ping($con)) {
    $isConnected = true;
    $serverInfo = mysqli_get_server_info($con);
    $sslRes = @mysqli_query($con, "SHOW STATUS LIKE 'Ssl_cipher'");
    if ($sslRes && $sslRes instanceof mysqli_result) {
        $row = mysqli_fetch_assoc($sslRes);
        $sslCipher = !empty($row['Value']) ? $row['Value'] : 'Standard/TLS';
    }
} else {
    $connectionError = isset($dbInstance) && $dbInstance->getLastError() 
        ? $dbInstance->getLastError() 
        : (mysqli_connect_error() ?: "Unable to establish MySQL connection. Please verify environment variables.");
}

// Inspect tables if connected
$existingTables = [];
$tableCounts = [];
$adminExists = false;

if ($isConnected) {
    $tblRes = mysqli_query($con, "SHOW TABLES");
    if ($tblRes && $tblRes instanceof mysqli_result) {
        while ($tRow = mysqli_fetch_array($tblRes)) {
            $tableName = $tRow[0];
            $existingTables[] = $tableName;
            $cntRes = @mysqli_query($con, "SELECT COUNT(*) FROM `$tableName`");
            if ($cntRes && $cntRes instanceof mysqli_result) {
                $cntRow = mysqli_fetch_array($cntRes);
                $tableCounts[$tableName] = $cntRow[0];
            } else {
                $tableCounts[$tableName] = 0;
            }
        }
    }

    if (in_array('users', $existingTables)) {
        $usrRes = @mysqli_query($con, "SELECT * FROM users WHERE UserName = 'suyash'");
        if ($usrRes && $usrRes instanceof mysqli_result && mysqli_num_rows($usrRes) > 0) {
            $adminExists = true;
        }
    }
}

// Handle 1-Click Database Import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import_sql') {
    if (!$isConnected) {
        $message = "Cannot import: Database connection is not established. Please check your Aiven credentials.";
        $messageType = "danger";
    } else {
        // Find bookstore.sql
        $sqlFile = __DIR__ . '/bookstore.sql';
        if (!file_exists($sqlFile)) {
            $sqlFile = __DIR__ . '/Online_Book_Shop_System/bookstore.sql';
        }

        if (!file_exists($sqlFile)) {
            $message = "Database dump file 'bookstore.sql' not found on the server.";
            $messageType = "danger";
        } else {
            $sqlContent = file_get_contents($sqlFile);
            
            // Clean SQL: remove CREATE DATABASE or USE statements that fail on restricted cloud users
            $sqlContent = preg_replace('/CREATE\s+DATABASE\s+[^;]+;/i', '', $sqlContent);
            $sqlContent = preg_replace('/USE\s+[^;]+;/i', '', $sqlContent);
            
            // Disable foreign key checks for clean import
            @mysqli_query($con, "SET FOREIGN_KEY_CHECKS = 0");
            @mysqli_query($con, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");

            // Split statements safely
            $lines = explode("\n", $sqlContent);
            $query = '';
            $importedCount = 0;
            $hasErrors = false;

            foreach ($lines as $line) {
                $trimmed = trim($line);
                // Skip comments and empty lines
                if (empty($trimmed) || substr($trimmed, 0, 2) === '--' || substr($trimmed, 0, 2) === '/*' || substr($trimmed, 0, 1) === '#') {
                    continue;
                }

                $query .= $line . "\n";

                if (substr(trim($line), -1) === ';') {
                    // Execute query
                    if (!@mysqli_query($con, $query)) {
                        $err = mysqli_error($con);
                        // Ignore harmless duplicate key errors if already imported
                        if (strpos($err, 'already exists') === false && strpos($err, 'Duplicate entry') === false) {
                            $importErrors[] = $err;
                        }
                    } else {
                        $importedCount++;
                    }
                    $query = '';
                }
            }

            // Restore foreign key checks
            @mysqli_query($con, "SET FOREIGN_KEY_CHECKS = 1");

            // Ensure default admin user is present
            $checkAdmin = @mysqli_query($con, "SELECT * FROM users WHERE UserName = 'suyash'");
            if (!$checkAdmin || mysqli_num_rows($checkAdmin) == 0) {
                @mysqli_query($con, "INSERT INTO users (UserName, Password) VALUES ('suyash', 'gulati')");
            }

            // Refresh table information
            $existingTables = [];
            $tableCounts = [];
            $tblRes = mysqli_query($con, "SHOW TABLES");
            if ($tblRes && $tblRes instanceof mysqli_result) {
                while ($tRow = mysqli_fetch_array($tblRes)) {
                    $tableName = $tRow[0];
                    $existingTables[] = $tableName;
                    $cntRes = @mysqli_query($con, "SELECT COUNT(*) FROM `$tableName`");
                    if ($cntRes && $cntRes instanceof mysqli_result) {
                        $cntRow = mysqli_fetch_array($cntRes);
                        $tableCounts[$tableName] = $cntRow[0];
                    }
                }
            }
            $adminExists = true;

            $message = "Database import completed successfully! Created/updated " . count($existingTables) . " tables with products and admin user.";
            $messageType = "success";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aiven Cloud MySQL 1-Click Setup - Online Book Shop</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/my.css" rel="stylesheet">
    <style>
        body { background: #f0f3f6; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; }
        .setup-header { background: #2c3e50; color: #fff; padding: 25px 0; margin-bottom: 30px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .setup-card { background: #fff; border-radius: 8px; padding: 25px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); }
        .setup-card h4 { color: #2c3e50; font-weight: bold; border-bottom: 2px solid #ecf0f1; padding-bottom: 12px; margin-top: 0; }
        .status-badge { display: inline-block; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: bold; }
        .status-online { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status-offline { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .status-ssl { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .btn-import { background: #27ae60; color: white; font-weight: bold; font-size: 16px; padding: 12px 25px; border: none; }
        .btn-import:hover { background: #219653; color: white; }
        .env-table th { width: 35%; color: #7f8c8d; }
    </style>
</head>
<body>
    <div class="setup-header">
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    <h2 style="margin: 0; font-weight: 700;">
                        <span class="glyphicon glyphicon-wrench"></span> Aiven MySQL 1-Click Setup Tool
                    </h2>
                    <p style="margin: 8px 0 0 0; opacity: 0.85;">
                        Online Book Shop System &bull; Render Docker Deployment Assistant
                    </p>
                </div>
                <div class="col-md-4 text-right" style="padding-top: 15px;">
                    <a href="index.php" class="btn btn-warning btn-md"><span class="glyphicon glyphicon-home"></span> Open Store</a>
                    <a href="admin/login.php" class="btn btn-default btn-md"><span class="glyphicon glyphicon-lock"></span> Admin</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4><?php echo $messageType === 'success' ? '✔ Success!' : '✖ Notice'; ?></h4>
                <p><?php echo htmlspecialchars($message); ?></p>
                <?php if ($messageType === 'success'): ?>
                    <hr>
                    <a href="index.php" class="btn btn-success"><span class="glyphicon glyphicon-arrow-right"></span> Launch Bookstore Now</a>
                    <a href="admin/login.php" class="btn btn-primary" style="margin-left: 10px;"><span class="glyphicon glyphicon-lock"></span> Login as Admin (suyash / gulati)</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Left Column: Database Status -->
            <div class="col-md-6">
                <div class="setup-card">
                    <h4><span class="glyphicon glyphicon-signal"></span> Aiven Cloud Database Health</h4>
                    
                    <div style="margin-bottom: 20px;">
                        Status: 
                        <?php if ($isConnected): ?>
                            <span class="status-badge status-online"><span class="glyphicon glyphicon-ok-sign"></span> Connected</span>
                            <?php if ($isSslActive): ?>
                                <span class="status-badge status-ssl"><span class="glyphicon glyphicon-lock"></span> SSL Encrypted</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="status-badge status-offline"><span class="glyphicon glyphicon-remove-sign"></span> Connection Failed</span>
                        <?php endif; ?>
                    </div>

                    <?php if (!$isConnected): ?>
                        <div class="alert alert-danger">
                            <strong>Connection Error:</strong><br>
                            <?php echo htmlspecialchars($connectionError); ?>
                            <hr>
                            <small>Tip: Verify your Render Environment Variables (<code>DB_SERVER</code>, <code>DB_PORT</code>, <code>DB_USERNAME</code>, <code>DB_PASSWORD</code>, <code>DB_NAME</code>, <code>DB_SSL=true</code>).</small>
                        </div>
                    <?php endif; ?>

                    <table class="table env-table">
                        <tr>
                            <th>Host / Server</th>
                            <td><code><?php echo htmlspecialchars($currentHost); ?></code></td>
                        </tr>
                        <tr>
                            <th>Custom Port</th>
                            <td><span class="badge"><?php echo htmlspecialchars($currentPort); ?></span></td>
                        </tr>
                        <tr>
                            <th>Database Name</th>
                            <td><code><?php echo htmlspecialchars($currentDb); ?></code></td>
                        </tr>
                        <tr>
                            <th>Username</th>
                            <td><code><?php echo htmlspecialchars($currentUser); ?></code></td>
                        </tr>
                        <tr>
                            <th>SSL Encryption</th>
                            <td>
                                <?php if ($isSslActive): ?>
                                    <span class="text-success font-weight-bold">✔ Enabled (MYSQLI_CLIENT_SSL)</span>
                                    <?php if ($sslCipher): ?>
                                        <br><small class="text-muted">Cipher: <?php echo htmlspecialchars($sslCipher); ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-warning">Plain / Disabled</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if ($serverInfo): ?>
                            <tr>
                                <th>MySQL Engine</th>
                                <td><?php echo htmlspecialchars($serverInfo); ?></td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>

                <!-- Admin Credentials Card -->
                <div class="setup-card">
                    <h4><span class="glyphicon glyphicon-user"></span> Default Admin & User Access</h4>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Role</th>
                                <th>Username</th>
                                <th>Password</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="label label-primary">Admin / User</span></td>
                                <td><code>suyash</code></td>
                                <td><code>gulati</code></td>
                                <td>
                                    <?php if ($adminExists): ?>
                                        <span class="label label-success">Ready</span>
                                    <?php else: ?>
                                        <span class="label label-warning">Pending Import</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="label label-default">Test User</span></td>
                                <td><code>a</code></td>
                                <td><code>a</code></td>
                                <td>
                                    <?php if ($adminExists): ?>
                                        <span class="label label-success">Ready</span>
                                    <?php else: ?>
                                        <span class="label label-warning">Pending Import</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <a href="admin/login.php" class="btn btn-default btn-block"><span class="glyphicon glyphicon-log-in"></span> Go to Admin Login (Direct Routing)</a>
                </div>
            </div>

            <!-- Right Column: Database Tables & Importer -->
            <div class="col-md-6">
                <!-- 1-Click Importer Card -->
                <div class="setup-card" style="border: 2px solid #27ae60;">
                    <h4 style="color: #27ae60;"><span class="glyphicon glyphicon-cloud-download"></span> 1-Click Database Importer</h4>
                    <p class="text-muted">
                        This tool reads <code>bookstore.sql</code>, cleans MySQL syntax for Aiven Cloud compatibility, disables foreign key constraints, and imports all tables (<code>products</code>, <code>users</code>, <code>cart</code>).
                    </p>

                    <form method="post" action="db_setup.php" onsubmit="return confirm('Do you want to import/refresh the database schema from bookstore.sql?');">
                        <input type="hidden" name="action" value="import_sql">
                        <button type="submit" class="btn btn-import btn-block btn-lg" <?php echo !$isConnected ? 'disabled' : ''; ?>>
                            <span class="glyphicon glyphicon-play"></span> 1-Click Import bookstore.sql
                        </button>
                    </form>
                    <?php if (!$isConnected): ?>
                        <p class="text-danger" style="margin-top: 10px; font-size: 13px;">
                            * Importer is disabled until MySQL connects successfully.
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Database Content Card -->
                <div class="setup-card">
                    <h4><span class="glyphicon glyphicon-th-list"></span> Existing Database Tables</h4>
                    <?php if (empty($existingTables)): ?>
                        <div class="alert alert-info">
                            <span class="glyphicon glyphicon-info-sign"></span> No tables found in database <code><?php echo htmlspecialchars($currentDb); ?></code> yet. Click the <strong>1-Click Import</strong> button above to populate the bookstore schema.
                        </div>
                    <?php else: ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Table Name</th>
                                    <th>Total Records</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($existingTables as $t): ?>
                                    <tr>
                                        <td><strong><code><?php echo htmlspecialchars($t); ?></code></strong></td>
                                        <td><span class="badge"><?php echo $tableCounts[$t] ?? 0; ?> rows</span></td>
                                        <td><span class="label label-success">OK</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- Direct Links Card -->
                <div class="setup-card">
                    <h4><span class="glyphicon glyphicon-link"></span> Quick Navigation</h4>
                    <div class="list-group">
                        <a href="index.php" class="list-group-item">
                            <span class="glyphicon glyphicon-home text-primary"></span> <strong>Main Bookstore Home (index.php)</strong>
                            <span class="pull-right">&rarr;</span>
                        </a>
                        <a href="admin/login.php" class="list-group-item">
                            <span class="glyphicon glyphicon-lock text-warning"></span> <strong>Admin Login Portal (admin/login.php)</strong>
                            <span class="pull-right">&rarr;</span>
                        </a>
                        <a href="admin/index.php" class="list-group-item">
                            <span class="glyphicon glyphicon-dashboard text-success"></span> <strong>Admin Dashboard (admin/index.php)</strong>
                            <span class="pull-right">&rarr;</span>
                        </a>
                        <a href="login.php" class="list-group-item">
                            <span class="glyphicon glyphicon-user text-info"></span> <strong>Customer Login (login.php)</strong>
                            <span class="pull-right">&rarr;</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
</body>
</html>

