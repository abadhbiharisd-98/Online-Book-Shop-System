<?php
/**
 * Database Connection for Online Book Shop System
 * Compatible with PHP 8.x, Aiven Cloud MySQL (SSL enforce), and Render Environment
 */

// Safe error reporting configuration
if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
}

// Disable PHP 8.1+ automatic mysqli_sql_exception on query errors
if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

class DatabaseConnection {
    // PHP 8.x safe: Class properties declared without dynamic function call initializers
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $database;
    private bool $useSsl;
    private ?mysqli $connection = null;
    private ?string $lastError = null;

    public function __construct() {
        // Parse DATABASE_URL / MYSQL_URL if provided (Standard cloud service format)
        $databaseUrl = getenv('DATABASE_URL') ?: getenv('MYSQL_URL');
        $urlParts = $databaseUrl ? parse_url($databaseUrl) : [];

        // Safely resolve values inside __construct (PHP 8.x compliant)
        $this->host = getenv('DB_SERVER') ?: (getenv('DB_HOST') ?: ($urlParts['host'] ?? 'localhost'));
        
        $portEnv = getenv('DB_PORT') ?: (getenv('MYSQLPORT') ?: ($urlParts['port'] ?? 3306));
        $this->port = (int)$portEnv;

        $this->username = getenv('DB_USERNAME') ?: (getenv('DB_USER') ?: ($urlParts['user'] ?? 'root'));
        $this->password = getenv('DB_PASSWORD') ?: (getenv('DB_PASS') ?: ($urlParts['pass'] ?? ''));
        
        $dbNameEnv = getenv('DB_NAME') ?: (getenv('MYSQLDATABASE') ?: (isset($urlParts['path']) ? ltrim($urlParts['path'], '/') : 'bookstore'));
        $this->database = $dbNameEnv;

        // Auto-enable SSL if DB_SSL is set, or if host is aivencloud.com, or port != 3306
        $sslEnv = getenv('DB_SSL');
        if ($sslEnv !== false && $sslEnv !== null && $sslEnv !== '') {
            $this->useSsl = in_array(strtolower((string)$sslEnv), ['true', '1', 'yes', 'require', 'on'], true);
        } else {
            // Auto-detect Aiven Cloud host or remote custom port
            $this->useSsl = (strpos($this->host, 'aivencloud.com') !== false || ($this->port !== 3306 && $this->host !== 'localhost' && $this->host !== '127.0.0.1'));
        }

        $this->connect();
    }

    private function connect(): void {
        $con = mysqli_init();
        if (!$con) {
            $this->lastError = "mysqli_init() initialization failed";
            return;
        }

        // 10-second connection timeout
        $con->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);

        if ($this->useSsl) {
            // Aiven MySQL enforces require_secure_transport=ON
            // MYSQLI_CLIENT_SSL enables SSL transport handshake
            $con->ssl_set(NULL, NULL, NULL, NULL, NULL);
            $connected = @$con->real_connect(
                $this->host,
                $this->username,
                $this->password,
                $this->database,
                $this->port,
                NULL,
                MYSQLI_CLIENT_SSL
            );

            // If SSL connect failed and host is localhost, try non-SSL as fallback
            if (!$connected && ($this->host === 'localhost' || $this->host === '127.0.0.1')) {
                $connected = @$con->real_connect(
                    $this->host,
                    $this->username,
                    $this->password,
                    $this->database,
                    $this->port
                );
            }
        } else {
            $connected = @$con->real_connect(
                $this->host,
                $this->username,
                $this->password,
                $this->database,
                $this->port
            );
        }

        if ($connected) {
            $con->set_charset('utf8mb4');
            $this->connection = $con;
        } else {
            $this->lastError = mysqli_connect_error() ?: "Failed to connect to MySQL database.";
            $this->connection = null;
        }
    }

    public function getConnection(): ?mysqli {
        return $this->connection;
    }

    public function getLastError(): ?string {
        return $this->lastError;
    }

    public function isConnected(): bool {
        return $this->connection !== null && @mysqli_ping($this->connection);
    }

    public function getHost(): string { return $this->host; }
    public function getPort(): int { return $this->port; }
    public function getUser(): string { return $this->username; }
    public function getDatabase(): string { return $this->database; }
    public function isSsl(): bool { return $this->useSsl; }
}

// Global Singleton Instance
$dbInstance = new DatabaseConnection();
$con = $dbInstance->getConnection();
$db = $con ? true : false;

// Define legacy constants for backwards compatibility
if (!defined('DB_HOST')) define('DB_HOST', $dbInstance->getHost());
if (!defined('DB_PORT')) define('DB_PORT', $dbInstance->getPort());
if (!defined('DB_USER')) define('DB_USER', $dbInstance->getUser());
if (!defined('DB_PASSWORD')) define('DB_PASSWORD', '');
if (!defined('DB_NAME')) define('DB_NAME', $dbInstance->getDatabase());
if (!defined('BASE_URL')) {
    $baseUrl = getenv('BASE_URL');
    if (!$baseUrl) {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = $protocol . '://' . $host;
    }
    define('BASE_URL', rtrim($baseUrl, '/'));
}

// Helper functions for safe database interaction (PHP 8.x null-safety)
function safe_query($connection, string $query) {
    if (!$connection || !($connection instanceof mysqli)) {
        return false;
    }
    return @mysqli_query($connection, $query);
}

function safe_num_rows($result): int {
    if ($result && $result instanceof mysqli_result) {
        return mysqli_num_rows($result);
    }
    return 0;
}

function safe_fetch_assoc($result): ?array {
    if ($result && $result instanceof mysqli_result) {
        $row = mysqli_fetch_assoc($result);
        return $row !== null ? $row : null;
    }
    return null;
}
