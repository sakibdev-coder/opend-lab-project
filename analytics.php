<?php
// ====================================================================
// Analytics & Reports (GROUP BY, Aggregations, and SUBQUERY Demonstrations)
// System: Social Development Events Platform
// ====================================================================

require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();

$pageTitle = "Platform Analytics & Data Aggregations";

// --------------------------------------------------------------------
// Query 1: GROUP BY on Categories with Aggregations (COUNT, SUM, COALESCE)
// --------------------------------------------------------------------
$categoryStatsSql = "
    SELECT 
        c.category_id,
        c.category_name,
        COUNT(DISTINCT e.event_id) AS total_events,
        COUNT(r.registration_id) AS total_registrations,
        COUNT(DISTINCT d.donation_id) AS total_donations_logged,
        COALESCE(SUM(CASE WHEN d.unit = 'BDT' THEN d.quantity ELSE 0 END), 0) AS total_cash_bdt
    FROM `categories` c
    LEFT JOIN `events` e ON c.category_id = e.category_id
    LEFT JOIN `registrations` r ON e.event_id = r.event_id
    LEFT JOIN `donations` d ON e.event_id = d.event_id
    GROUP BY c.category_id, c.category_name
    ORDER BY total_events DESC, total_registrations DESC
";
$categoryStats = $pdo->query($categoryStatsSql)->fetchAll();

// --------------------------------------------------------------------
// Query 2: GROUP BY on Area / Localities across Dhaka
// --------------------------------------------------------------------
$areaStatsSql = "
    SELECT 
        e.area,
        COUNT(DISTINCT e.event_id) AS total_events,
        COUNT(r.registration_id) AS total_volunteers,
        COUNT(DISTINCT CASE WHEN e.status = 'Upcoming' THEN e.event_id END) AS upcoming_drives,
        COUNT(DISTINCT CASE WHEN e.status = 'Ongoing' THEN e.event_id END) AS ongoing_drives,
        COUNT(DISTINCT CASE WHEN e.status = 'Completed' THEN e.event_id END) AS completed_drives
    FROM `events` e
    LEFT JOIN `registrations` r ON e.event_id = r.event_id
    GROUP BY e.area
    ORDER BY total_events DESC, total_volunteers DESC
";
$areaStats = $pdo->query($areaStatsSql)->fetchAll();

// --------------------------------------------------------------------
// Query 3: SUBQUERY Demonstration (Above-Average Volunteer Mobilization)
// Finds events whose volunteer signups are >= average signups across all active events
// --------------------------------------------------------------------
$subquerySql = "
    SELECT 
        e.event_id,
        e.title,
        e.area,
        e.status,
        e.event_date,
        (SELECT COUNT(*) FROM `registrations` r WHERE r.event_id = e.event_id) AS event_volunteers,
        ROUND((
            SELECT AVG(reg_count) 
            FROM (
                SELECT COUNT(*) AS reg_count 
                FROM `registrations` 
                GROUP BY event_id
            ) AS temp_avg
        ), 2) AS system_average_volunteers
    FROM `events` e
    WHERE (SELECT COUNT(*) FROM `registrations` r WHERE r.event_id = e.event_id) >= (
        SELECT AVG(reg_count) 
        FROM (
            SELECT COUNT(*) AS reg_count 
            FROM `registrations` 
            GROUP BY event_id
        ) AS temp_avg2
    )
    ORDER BY event_volunteers DESC
";
$highEngagementEvents = $pdo->query($subquerySql)->fetchAll();

// Calculate platform totals
$totalEventsAcross = array_sum(array_column($categoryStats, 'total_events'));
$totalRegAcross = array_sum(array_column($categoryStats, 'total_registrations'));
$totalCashAcross = array_sum(array_column($categoryStats, 'total_cash_bdt'));

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1"><i class="fas fa-chart-pie text-success me-2"></i>Platform Analytics & Data Aggregation</h2>
        <p class="text-muted mb-0">Live aggregated statistics using SQL <code>GROUP BY</code>, <code>HAVING</code>, and correlated <code>SUBQUERIES</code>.</p>
    </div>
    <div>
        <a href="queries-showcase.php" class="btn btn-warning fw-bold">
            <i class="fas fa-code me-1"></i>View All 10+ Queries
        </a>
    </div>
</div>

<!-- Quick Highlights -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="stat-icon bg-success-subtle text-success">
                <i class="fas fa-bullseye"></i>
            </div>
            <div>
                <h3 class="fw-bold mb-0"><?= $totalEventsAcross; ?></h3>
                <span class="text-muted small">Total Events Across Dhaka</span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="stat-icon bg-primary-subtle text-primary">
                <i class="fas fa-user-friends"></i>
            </div>
            <div>
                <h3 class="fw-bold mb-0"><?= $totalRegAcross; ?></h3>
                <span class="text-muted small">Total Volunteer Participations</span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="stat-icon bg-warning-subtle text-warning">
                <i class="fas fa-hand-holding-usd"></i>
            </div>
            <div>
                <h3 class="fw-bold mb-0">৳ <?= number_format($totalCashAcross, 2); ?></h3>
                <span class="text-muted small">Cash Relief Mobilized (BDT)</span>
            </div>
        </div>
    </div>
</div>

<!-- Section 1: GROUP BY on Categories -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0 fw-bold text-dark">
                <i class="fas fa-tags text-primary me-2"></i>Category-wise Social Drive Distribution
            </h5>
            <small class="text-muted">Demonstrates: <code>GROUP BY c.category_id, c.category_name</code> with <code>COUNT(DISTINCT)</code> and <code>SUM()</code></small>
        </div>
        <span class="badge bg-primary">GROUP BY Query #1</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#ID</th>
                        <th>Category Name</th>
                        <th class="text-center">Total Events</th>
                        <th class="text-center">Volunteer Signups</th>
                        <th class="text-center">Donation Records</th>
                        <th class="text-end">Cash Aid Raised</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categoryStats as $c): ?>
                        <tr>
                            <td class="fw-bold text-muted">#<?= e($c['category_id']); ?></td>
                            <td>
                                <strong><?= e($c['category_name']); ?></strong>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success fs-6"><?= e($c['total_events']); ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-info text-dark fs-6"><?= e($c['total_registrations']); ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border"><?= e($c['total_donations_logged']); ?></span>
                            </td>
                            <td class="text-end fw-bold text-success">
                                ৳ <?= number_format($c['total_cash_bdt'], 2); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Section 2: GROUP BY on Area / Localities -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0 fw-bold text-dark">
                <i class="fas fa-map-marked-alt text-danger me-2"></i>Dhaka Area Breakdown (Mirpur, Mohammadpur, Dhanmondi, etc.)
            </h5>
            <small class="text-muted">Demonstrates: <code>GROUP BY e.area</code> with conditional aggregation</small>
        </div>
        <span class="badge bg-danger">GROUP BY Query #2</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Dhaka Area / Locality</th>
                        <th class="text-center">Total Events</th>
                        <th class="text-center">Volunteers</th>
                        <th class="text-center">Upcoming</th>
                        <th class="text-center">Ongoing</th>
                        <th class="text-center">Completed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($areaStats as $a): ?>
                        <tr>
                            <td>
                                <strong><i class="fas fa-map-pin text-danger me-2"></i><?= e($a['area']); ?></strong>
                            </td>
                            <td class="text-center fw-bold"><?= e($a['total_events']); ?></td>
                            <td class="text-center"><span class="badge bg-primary"><?= e($a['total_volunteers']); ?></span></td>
                            <td class="text-center"><span class="badge bg-info text-dark"><?= e($a['upcoming_drives']); ?></span></td>
                            <td class="text-center"><span class="badge bg-warning text-dark"><?= e($a['ongoing_drives']); ?></span></td>
                            <td class="text-center"><span class="badge bg-success"><?= e($a['completed_drives']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Section 3: SUBQUERY Demonstration -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0 fw-bold text-dark">
                <i class="fas fa-fire text-warning me-2"></i>High-Impact Events (Volunteer Signups $\ge$ System Average)
            </h5>
            <small class="text-muted">Demonstrates nested SQL <strong>SUBQUERY</strong> with aggregate comparison: <code>WHERE COUNT(*) >= (SELECT AVG(...) FROM ...)</code></small>
        </div>
        <span class="badge bg-warning text-dark">SUBQUERY Query #3</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#ID</th>
                        <th>Event Title</th>
                        <th>Area</th>
                        <th>Status</th>
                        <th class="text-center">Volunteers Enrolled</th>
                        <th class="text-center">Overall Platform Average</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($highEngagementEvents)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No events meet the criteria yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($highEngagementEvents as $h): ?>
                            <tr>
                                <td class="fw-bold text-muted">#<?= e($h['event_id']); ?></td>
                                <td>
                                    <strong><?= e($h['title']); ?></strong>
                                    <div class="small text-muted"><?= formatDate($h['event_date']); ?></div>
                                </td>
                                <td><?= e($h['area']); ?></td>
                                <td><?= getStatusBadge($h['status']); ?></td>
                                <td class="text-center">
                                    <span class="badge bg-success fs-6"><?= e($h['event_volunteers']); ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary"><?= e($h['system_average_volunteers'] ?? '0'); ?> avg</span>
                                </td>
                                <td class="text-end">
                                    <a href="event-details.php?id=<?= e($h['event_id']); ?>" class="btn btn-sm btn-outline-primary">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
