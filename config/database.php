<?php
// ====================================================================
// Database Configuration and Connection Module (PDO)
// System: Social Development Events Platform
// ====================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Credentials (Standard for XAMPP / MAMP / Localhost)
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'social_events_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a configured PDO database connection instance.
 *
 * @return PDO
 */
function getDBConnection() {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Check if database needs initial setup
            $setupUrl = 'setup_db.php';
            die("
                <div style='font-family: sans-serif; max-width: 600px; margin: 40px auto; padding: 25px; border: 1px solid #f5c6cb; background: #f8d7da; border-radius: 8px; color: #721c24;'>
                    <h2 style='margin-top:0;'>⚠️ Database Connection Failed</h2>
                    <p>Could not connect to MySQL database <strong>" . htmlspecialchars(DB_NAME) . "</strong>.</p>
                    <p style='font-size: 13px; color: #555; background: #fff; padding: 10px; border-radius: 4px; overflow-x: auto;'>
                        Error: " . htmlspecialchars($e->getMessage()) . "
                    </p>
                    <hr style='border: none; border-top: 1px solid #f5c6cb;'>
                    <p><strong>Troubleshooting Steps:</strong></p>
                    <ol>
                        <li>Make sure <strong>MySQL is running</strong> in XAMPP / MAMP Control Panel.</li>
                        <li>If the database has not been created yet, click the button below to initialize it automatically:</li>
                    </ol>
                    <p style='text-align: center; margin-top: 20px;'>
                        <a href='{$setupUrl}' style='background: #0d6efd; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Run 1-Click Database Setup</a>
                    </p>
                </div>
            ");
        }
    }

    return $pdo;
}

/**
 * Sanitize and escape text for HTML output
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Format date for friendly display
 */
function formatDate($dateString, $format = 'M d, Y h:i A') {
    if (!$dateString) return 'N/A';
    try {
        $date = new DateTime($dateString);
        return $date->format($format);
    } catch (Exception $e) {
        return $dateString;
    }
}

/**
 * Render visual Bootstrap badge for event and registration statuses
 */
function getStatusBadge($status) {
    switch ($status) {
        case 'Upcoming':
            return '<span class="badge bg-primary"><i class="fas fa-calendar-check me-1"></i>Upcoming</span>';
        case 'Ongoing':
            return '<span class="badge bg-warning text-dark"><i class="fas fa-spinner fa-spin me-1"></i>Ongoing</span>';
        case 'Completed':
            return '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Completed</span>';
        case 'Cancelled':
            return '<span class="badge bg-danger"><i class="fas fa-ban me-1"></i>Cancelled</span>';
        case 'Registered':
            return '<span class="badge bg-info text-dark">Registered</span>';
        case 'Confirmed':
            return '<span class="badge bg-primary">Confirmed</span>';
        case 'Attended':
            return '<span class="badge bg-success">Attended</span>';
        default:
            return '<span class="badge bg-secondary">' . e($status) . '</span>';
    }
}

/**
 * Flash messaging helpers
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'text' => $message
    ];
}

function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        $icon = $msg['type'] === 'success' ? 'fa-check-circle' : ($msg['type'] === 'danger' ? 'fa-exclamation-triangle' : 'fa-info-circle');
        return "
            <div class='alert alert-{$msg['type']} alert-dismissible fade show' role='alert'>
                <i class='fas {$icon} me-2'></i> " . e($msg['text']) . "
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
            </div>
        ";
    }
    return '';
}
