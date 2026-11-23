<?php
// ====================================================================
// Automated 1-Click Database Initializer & Migration Tool
// System: Social Development Events Platform
// ====================================================================

require_once __DIR__ . '/config/database.php';

$message = '';
$status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_setup'])) {
    try {
        // Connect to MySQL server without selecting a database first
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Read schema.sql
        $schemaFile = __DIR__ . '/database/schema.sql';
        if (!file_exists($schemaFile)) {
            throw new Exception("schema.sql file not found at " . $schemaFile);
        }

        $sql = file_get_contents($schemaFile);

        // Execute multiple SQL statements
        $pdo->exec($sql);

        $status = 'success';
        $message = "Database 'social_events_db', all 5 tables (USERS, CATEGORIES, EVENTS, REGISTRATIONS, DONATIONS), the SQL VIEW 'vw_event_summary', and Dhaka sample data have been initialized successfully!";
    } catch (Exception $e) {
        $status = 'danger';
        $message = "Error initializing database: " . $e->getMessage();
    }
}

$pageTitle = "Database Setup & Initializer";
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center my-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white py-3">
                <h4 class="mb-0"><i class="fas fa-database me-2"></i>Database Initializer & Seed Tool</h4>
            </div>
            <div class="card-body p-4">
                <?php if ($status): ?>
                    <div class="alert alert-<?= $status; ?> alert-dismissible fade show" role="alert">
                        <strong><?= $status === 'success' ? 'Great Success!' : 'Error:'; ?></strong> <?= e($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php if ($status === 'success'): ?>
                        <div class="text-center my-4">
                            <a href="index.php" class="btn btn-success btn-lg px-4 me-2">
                                <i class="fas fa-home me-2"></i>Go to Home Dashboard
                            </a>
                            <a href="events.php" class="btn btn-outline-primary btn-lg px-4">
                                <i class="fas fa-calendar-alt me-2"></i>Explore Events
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <p class="lead">
                    This setup script configures the MySQL database, runs all 3NF schema tables, foreign key constraints, checks, view, and sample records for Dhaka social development events.
                </p>

                <div class="card bg-light border-0 mb-4">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3"><i class="fas fa-info-circle text-primary me-2"></i>What will be configured:</h6>
                        <ul class="mb-0">
                            <li>Database: <code>social_events_db</code></li>
                            <li><strong>5 Relational Tables (3NF):</strong> <code>users</code>, <code>categories</code>, <code>events</code>, <code>registrations</code>, <code>donations</code></li>
                            <li><strong>Integrity Constraints:</strong> Primary Keys, Foreign Keys (CASCADE / RESTRICT), CHECK constraints</li>
                            <li><strong>SQL VIEW:</strong> <code>vw_event_summary</code> for optimized read operations</li>
                            <li><strong>Dhaka Sample Events:</strong> Mirpur 10 Winter Clothes Distribution, Mohammadpur Blanket Distribution, Korail Slum Health Camp, Dhanmondi Lake Plantation, etc.</li>
                        </ul>
                    </div>
                </div>

                <form method="POST" onsubmit="return confirm('Initialize / Reset the database with default schema and sample data?');">
                    <div class="d-grid gap-2">
                        <button type="submit" name="run_setup" value="1" class="btn btn-primary btn-lg">
                            <i class="fas fa-play-circle me-2"></i>Initialize / Reset Database Now
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
