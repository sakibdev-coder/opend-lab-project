<?php
// ====================================================================
// Events Explorer: Read & Search Operations with Multi-Table JOIN
// System: Social Development Events Platform
// ====================================================================

require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();

$pageTitle = "Explore Social Development Events";

// 1. Fetch categories for filter dropdown
$categories = $pdo->query("SELECT * FROM `categories` ORDER BY category_name ASC")->fetchAll();

// 2. Fetch distinct areas for area dropdown
$areasStmt = $pdo->query("SELECT DISTINCT area FROM `events` ORDER BY area ASC");
$areas = $areasStmt->fetchAll(PDO::FETCH_COLUMN);

// 3. Build Search / Filter Query using PDO Prepared Statements (Multi-table JOIN)
// Meets requirement: Read: search and display records, including data combined from more than one table.
$searchKeyword = trim($_GET['q'] ?? '');
$filterCategory = trim($_GET['category_id'] ?? '');
$filterArea = trim($_GET['area'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');

$sql = "
    SELECT 
        e.event_id,
        e.title,
        e.description,
        e.area,
        e.exact_location,
        e.event_date,
        e.target_volunteers,
        e.status,
        e.created_at,
        c.category_id,
        c.category_name,
        u.user_id AS organizer_id,
        u.full_name AS organizer_name,
        u.email AS organizer_email,
        u.phone AS organizer_phone,
        (SELECT COUNT(*) FROM `registrations` r WHERE r.event_id = e.event_id) AS total_registered,
        (SELECT COUNT(*) FROM `donations` d WHERE d.event_id = e.event_id) AS total_donations
    FROM `events` e
    INNER JOIN `categories` c ON e.category_id = c.category_id
    INNER JOIN `users` u ON e.organizer_id = u.user_id
    WHERE 1=1
";

$params = [];

if ($searchKeyword !== '') {
    $sql .= " AND (e.title LIKE :kw OR e.description LIKE :kw OR e.exact_location LIKE :kw OR e.area LIKE :kw)";
    $params[':kw'] = '%' . $searchKeyword . '%';
}

if ($filterCategory !== '') {
    $sql .= " AND e.category_id = :cat_id";
    $params[':cat_id'] = $filterCategory;
}

if ($filterArea !== '') {
    $sql .= " AND e.area = :area";
    $params[':area'] = $filterArea;
}

if ($filterStatus !== '') {
    $sql .= " AND e.status = :status";
    $params[':status'] = $filterStatus;
}

$sql .= " ORDER BY e.event_date ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
        <h2 class="fw-bold mb-1"><i class="fas fa-list-alt text-success me-2"></i>Social Welfare Events in Dhaka</h2>
        <p class="text-muted mb-0">Browse, search, and join local initiatives across Dhaka neighborhoods.</p>
    </div>
    <div>
        <a href="event-create.php" class="btn btn-success fw-bold">
            <i class="fas fa-plus-circle me-1"></i> Create New Event
        </a>
    </div>
</div>

<!-- Search & Multi-Criteria Filter Card -->
<div class="card shadow-sm border-0 mb-4 bg-white">
    <div class="card-body p-3">
        <form action="events.php" method="GET" class="row g-2 align-items-center">
            <div class="col-lg-4 col-md-6">
                <label class="form-label small fw-bold text-muted mb-1">Search Keywords</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Search title, location, description..." value="<?= e($searchKeyword); ?>">
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <label class="form-label small fw-bold text-muted mb-1">Category</label>
                <select name="category_id" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat['category_id']); ?>" <?= ($filterCategory == $cat['category_id']) ? 'selected' : ''; ?>>
                            <?= e($cat['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-lg-2 col-md-4">
                <label class="form-label small fw-bold text-muted mb-1">Area / Locality</label>
                <select name="area" class="form-select">
                    <option value="">All Areas</option>
                    <?php foreach ($areas as $a): ?>
                        <option value="<?= e($a); ?>" <?= ($filterArea == $a) ? 'selected' : ''; ?>>
                            <?= e($a); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-lg-2 col-md-4">
                <label class="form-label small fw-bold text-muted mb-1">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="Upcoming" <?= ($filterStatus === 'Upcoming') ? 'selected' : ''; ?>>Upcoming</option>
                    <option value="Ongoing" <?= ($filterStatus === 'Ongoing') ? 'selected' : ''; ?>>Ongoing</option>
                    <option value="Completed" <?= ($filterStatus === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                    <option value="Cancelled" <?= ($filterStatus === 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>

            <div class="col-lg-1 col-md-4 d-grid">
                <label class="form-label small fw-bold text-muted mb-1 d-none d-md-block">&nbsp;</label>
                <button type="submit" class="btn btn-primary fw-bold" title="Apply Filter">
                    <i class="fas fa-filter"></i>
                </button>
            </div>
        </form>

        <?php if ($searchKeyword !== '' || $filterCategory !== '' || $filterArea !== '' || $filterStatus !== ''): ?>
            <div class="mt-2 pt-2 border-top d-flex align-items-center justify-content-between">
                <span class="small text-muted">Showing filtered results (<?= count($events); ?> found)</span>
                <a href="events.php" class="btn btn-link btn-sm text-decoration-none text-danger p-0">
                    <i class="fas fa-times-circle me-1"></i>Clear all filters
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Events Table and Grid View -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">
            <i class="fas fa-calendar-check text-success me-2"></i>Events List (<?= count($events); ?> records)
        </h5>
        <span class="badge bg-secondary">Multi-Table JOIN: events + categories + users</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($events)): ?>
            <div class="p-5 text-center text-muted">
                <i class="fas fa-folder-open fa-3x mb-3 text-secondary"></i>
                <h5>No events matched your search criteria.</h5>
                <p>Try resetting filters or create a new social event.</p>
                <a href="events.php" class="btn btn-outline-primary btn-sm me-2">Reset Filters</a>
                <a href="event-create.php" class="btn btn-success btn-sm">Create Event</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#ID</th>
                            <th>Event Title & Category</th>
                            <th>Area & Location</th>
                            <th>Date & Time</th>
                            <th>Organizer</th>
                            <th>Volunteers</th>
                            <th>Status</th>
                            <th class="text-end">Actions (CRUD)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $ev): 
                            $pct = $ev['target_volunteers'] > 0 ? min(100, round(($ev['total_registered'] / $ev['target_volunteers']) * 100)) : 0;
                        ?>
                            <tr>
                                <td class="fw-bold text-muted">#<?= e($ev['event_id']); ?></td>
                                <td>
                                    <a href="event-details.php?id=<?= e($ev['event_id']); ?>" class="fw-bold text-dark text-decoration-none">
                                        <?= e($ev['title']); ?>
                                    </a>
                                    <br>
                                    <span class="badge bg-light text-dark border"><?= e($ev['category_name']); ?></span>
                                    <?php if ($ev['total_donations'] > 0): ?>
                                        <span class="badge bg-info text-dark ms-1" title="Donations logged">
                                            <i class="fas fa-gift me-1"></i><?= $ev['total_donations']; ?> items
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><i class="fas fa-map-marker-alt text-danger me-1"></i><?= e($ev['area']); ?></strong>
                                    <div class="small text-muted"><?= e($ev['exact_location']); ?></div>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= formatDate($ev['event_date'], 'M d, Y'); ?></div>
                                    <small class="text-muted"><?= formatDate($ev['event_date'], 'h:i A'); ?></small>
                                </td>
                                <td>
                                    <div class="small fw-bold"><?= e($ev['organizer_name']); ?></div>
                                    <div class="text-muted small"><?= e($ev['organizer_phone']); ?></div>
                                </td>
                                <td style="min-width: 130px;">
                                    <div class="d-flex justify-content-between small text-muted mb-1">
                                        <span><strong><?= e($ev['total_registered']); ?></strong> / <?= e($ev['target_volunteers']); ?></span>
                                        <span><?= $pct; ?>%</span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-success" style="width: <?= $pct; ?>%"></div>
                                    </div>
                                </td>
                                <td>
                                    <?= getStatusBadge($ev['status']); ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="event-details.php?id=<?= e($ev['event_id']); ?>" class="btn btn-outline-primary" title="View Details & Registrations (Read)">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="event-edit.php?id=<?= e($ev['event_id']); ?>" class="btn btn-outline-warning" title="Edit Event (Update)">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="event-delete.php?id=<?= e($ev['event_id']); ?>" class="btn btn-outline-danger" title="Delete Event (Delete)" data-confirm="Are you sure you want to delete event '#<?= e($ev['event_id']); ?> - <?= addslashes(e($ev['title'])); ?>'? This will also delete registrations and donations.">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
