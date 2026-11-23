<?php
// ====================================================================
// Home Dashboard
// System: Social Development Events Platform
// ====================================================================

require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();

$pageTitle = "Home Dashboard - Local Social Welfare Initiatives";

// 1. KPI Counts
$totalEvents = $pdo->query("SELECT COUNT(*) FROM `events`")->fetchColumn();
$totalVolunteers = $pdo->query("SELECT COUNT(*) FROM `registrations` WHERE `status` IN ('Registered', 'Confirmed', 'Attended')")->fetchColumn();
$totalDonations = $pdo->query("SELECT COUNT(*) FROM `donations`")->fetchColumn();
$upcomingEventsCount = $pdo->query("SELECT COUNT(*) FROM `events` WHERE `status` = 'Upcoming'")->fetchColumn();

// 2. Fetch Featured Events using SQL VIEW (vw_event_summary)
$viewQuery = "
    SELECT 
        event_id, title, area, exact_location, event_date, status, 
        target_volunteers, category_name, organizer_name, 
        total_volunteers, confirmed_volunteers, total_donations
    FROM `vw_event_summary`
    ORDER BY 
        CASE WHEN status = 'Upcoming' THEN 1 WHEN status = 'Ongoing' THEN 2 ELSE 3 END,
        event_date ASC
    LIMIT 6
";
$stmt = $pdo->query($viewQuery);
$featuredEvents = $stmt->fetchAll();

// 3. Categories for quick search
$categories = $pdo->query("SELECT category_id, category_name FROM `categories` ORDER BY category_name ASC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Banner -->
<div class="hero-banner text-center text-md-start">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <span class="badge bg-warning text-dark px-3 py-2 fw-bold text-uppercase mb-3">
                <i class="fas fa-hand-holding-heart me-1"></i> Community Care Dhaka
            </span>
            <h1 class="display-5 fw-bold mb-3">Empower Your City Through Social Development</h1>
            <p class="lead mb-4 opacity-90">
                Join hands with local citizens to distribute winter clothes in Mirpur, blankets in Mohammadpur, conduct free health camps, and plant trees across Dhaka neighborhoods.
            </p>
            <div class="d-flex flex-wrap gap-2">
                <a href="events.php" class="btn btn-warning btn-lg fw-bold px-4">
                    <i class="fas fa-search me-2"></i>Find Local Events
                </a>
                <a href="event-create.php" class="btn btn-outline-light btn-lg px-4">
                    <i class="fas fa-calendar-plus me-2"></i>Create New Event
                </a>
                <a href="queries-showcase.php" class="btn btn-info text-dark btn-lg fw-bold px-3" title="Review for Viva">
                    <i class="fas fa-graduation-cap me-1"></i>Viva SQL Queries
                </a>
            </div>
        </div>
        <div class="col-lg-4 d-none d-lg-block text-center">
            <i class="fas fa-people-carry text-white-50" style="font-size: 11rem; opacity: 0.25;"></i>
        </div>
    </div>
</div>

<!-- Stat KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon bg-success-subtle text-success">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div>
                <h3 class="fw-bold mb-0"><?= e($totalEvents); ?></h3>
                <span class="text-muted small">Total Events</span>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon bg-primary-subtle text-primary">
                <i class="fas fa-users"></i>
            </div>
            <div>
                <h3 class="fw-bold mb-0"><?= e($totalVolunteers); ?></h3>
                <span class="text-muted small">Active Volunteers</span>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon bg-warning-subtle text-warning">
                <i class="fas fa-clock"></i>
            </div>
            <div>
                <h3 class="fw-bold mb-0"><?= e($upcomingEventsCount); ?></h3>
                <span class="text-muted small">Upcoming Drives</span>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon bg-info-subtle text-info">
                <i class="fas fa-gift"></i>
            </div>
            <div>
                <h3 class="fw-bold mb-0"><?= e($totalDonations); ?></h3>
                <span class="text-muted small">Supplies & Donations</span>
            </div>
        </div>
    </div>
</div>

<!-- Search Bar Form -->
<div class="card shadow-sm border-0 mb-5">
    <div class="card-body p-4 bg-white rounded-3">
        <h5 class="fw-bold mb-3"><i class="fas fa-search-location text-success me-2"></i>Search Social Service Events in Dhaka</h5>
        <form action="events.php" method="GET" class="row g-2">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Search by event title, location, or keyword (e.g., Blanket, Clothes, Health)...">
                </div>
            </div>
            <div class="col-md-3">
                <select name="category_id" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat['category_id']); ?>"><?= e($cat['category_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="area" class="form-select">
                    <option value="">All Areas</option>
                    <option value="Mirpur 10, Dhaka">Mirpur 10</option>
                    <option value="Mohammadpur, Dhaka">Mohammadpur</option>
                    <option value="Dhanmondi, Dhaka">Dhanmondi</option>
                    <option value="Banani, Dhaka">Banani / Korail</option>
                    <option value="Old Dhaka, Dhaka">Old Dhaka</option>
                    <option value="Farmgate, Dhaka">Farmgate</option>
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-success fw-bold">
                    <i class="fas fa-filter me-1"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Featured Events (from SQL VIEW vw_event_summary) -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-star text-warning me-2"></i>Featured Social Drives in Dhaka</h4>
        <small class="text-muted">Directly populated from SQL VIEW <code>vw_event_summary</code> (JOIN of 4 tables)</small>
    </div>
    <a href="events.php" class="btn btn-outline-success btn-sm fw-bold">
        View All Events <i class="fas fa-arrow-right ms-1"></i>
    </a>
</div>

<div class="row g-4 mb-5">
    <?php if (empty($featuredEvents)): ?>
        <div class="col-12">
            <div class="alert alert-info text-center py-4">
                <i class="fas fa-info-circle fa-2x mb-3 text-primary"></i>
                <h5>No events found in database!</h5>
                <p>Run the 1-click database setup to seed Dhaka social development events.</p>
                <a href="setup_db.php" class="btn btn-primary"><i class="fas fa-database me-1"></i>Setup & Seed Sample Data</a>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($featuredEvents as $ev): 
            $progress = $ev['target_volunteers'] > 0 ? min(100, round(($ev['total_volunteers'] / $ev['target_volunteers']) * 100)) : 0;
        ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 event-card">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="category-pill"><?= e($ev['category_name']); ?></span>
                            <?= getStatusBadge($ev['status']); ?>
                        </div>
                        <h5 class="card-title fw-bold mt-2 mb-2">
                            <a href="event-details.php?id=<?= e($ev['event_id']); ?>" class="text-dark text-decoration-none hover-primary">
                                <?= e($ev['title']); ?>
                            </a>
                        </h5>
                        <p class="text-muted small mb-2">
                            <i class="fas fa-map-marker-alt text-danger me-1"></i> <strong><?= e($ev['area']); ?></strong>
                            <br>
                            <span class="text-secondary"><?= e($ev['exact_location']); ?></span>
                        </p>
                        <p class="text-muted small mb-3">
                            <i class="fas fa-clock text-primary me-1"></i> <?= formatDate($ev['event_date']); ?>
                        </p>
                        
                        <!-- Volunteer Progress Bar -->
                        <div class="mt-auto pt-2">
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>Volunteers: <strong><?= e($ev['total_volunteers']); ?></strong> / <?= e($ev['target_volunteers']); ?></span>
                                <span><?= $progress; ?>%</span>
                            </div>
                            <div class="progress volunteer-progress mb-3">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?= $progress; ?>%"></div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                <small class="text-muted">
                                    <i class="fas fa-user-circle me-1"></i> <?= e($ev['organizer_name']); ?>
                                </small>
                                <a href="event-details.php?id=<?= e($ev['event_id']); ?>" class="btn btn-sm btn-outline-primary fw-bold">
                                    Details <i class="fas fa-chevron-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Lab & Viva Overview Box -->
<div class="card bg-dark text-white border-0 shadow-sm p-4 mb-3">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h5 class="text-warning fw-bold mb-2"><i class="fas fa-graduation-cap me-2"></i>Academic Lab Assignment & Viva Readiness</h5>
            <p class="text-light-50 mb-0 small">
                This project fulfills all coursework specifications: <strong>5 relational tables in 3NF</strong>, <strong>Primary & Foreign Keys with CASCADE/RESTRICT</strong>, prepared statements via <strong>PDO</strong>, full <strong>CRUD operations</strong>, and <strong>10+ distinct SQL queries</strong> (JOINs, GROUP BY, Subqueries, VIEW, INSERT, UPDATE, DELETE).
            </p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="queries-showcase.php" class="btn btn-warning fw-bold px-3">
                <i class="fas fa-terminal me-1"></i>Test Live 10+ Queries
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
